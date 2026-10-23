const http = require('http');
const { spawn } = require('child_process');
const path = require('path');
const fs = require('fs');

async function run() {
    console.log("=== STARTING BROWSER LOGOUT INTERACTION TEST (PORT 8081) ===");
    
    // Launch headless chrome
    const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
    const profileDir = path.join(__dirname, 'test_chrome_profile_logout');
    if (!fs.existsSync(profileDir)) fs.mkdirSync(profileDir, { recursive: true });

    const chrome = spawn(chromePath, [
        '--remote-debugging-port=9223',
        '--no-first-run',
        '--no-default-browser-check',
        '--headless=new',
        `--user-data-dir=${profileDir}`,
        'about:blank'
    ]);

    await new Promise(r => setTimeout(r, 2000));

    // Get websocket target
    const versionData = await new Promise((resolve, reject) => {
        http.get('http://127.0.0.1:9223/json/list', res => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => resolve(JSON.parse(data)));
        }).on('error', reject);
    });

    const pageTarget = versionData.find(t => t.type === 'page');
    console.log("Connected to Chrome target:", pageTarget.id);

    const ws = new WebSocket(pageTarget.webSocketDebuggerUrl);
    let msgId = 1;
    const callbacks = new Map();

    ws.onmessage = (event) => {
        const msg = JSON.parse(event.data);
        if (msg.method === 'Runtime.consoleAPICalled') {
            console.log(`[Browser Console ${msg.params.type}]:`, msg.params.args.map(a => a.value || a.description).join(' '));
        } else if (msg.method === 'Runtime.exceptionThrown') {
            console.error('[Browser Exception]:', msg.params.exceptionDetails.text, msg.params.exceptionDetails.exception?.description);
        }
        if (msg.id && callbacks.has(msg.id)) {
            const cb = callbacks.get(msg.id);
            callbacks.delete(msg.id);
            if (msg.error) cb.reject(msg.error);
            else cb.resolve(msg.result);
        }
    };

    await new Promise(r => { ws.onopen = r; });

    function send(method, params = {}) {
        return new Promise((resolve, reject) => {
            const id = msgId++;
            callbacks.set(id, { resolve, reject });
            ws.send(JSON.stringify({ id, method, params }));
        });
    }

    await send('Page.enable');
    await send('Runtime.enable');

    async function evaluate(expression) {
        const res = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
        if (res.exceptionDetails) {
            throw new Error(res.exceptionDetails.text + ': ' + (res.exceptionDetails.exception?.description || ''));
        }
        return res.result?.value;
    }

    // 1. Navigate to login page
    console.log("Navigating to http://127.0.0.1:8081/admin/login.php...");
    await send('Page.navigate', { url: 'http://127.0.0.1:8081/admin/login.php' });
    await new Promise(r => setTimeout(r, 1200));

    // 2. Perform Login in browser
    console.log("Submitting login form in browser...");
    await evaluate(`
        document.querySelector('input[name="username"]').value = 'admin';
        document.querySelector('input[name="password"]').value = 'admin123';
        document.querySelector('form').submit();
    `);
    await new Promise(r => setTimeout(r, 1500));

    let currentUrl = await evaluate(`window.location.href`);
    console.log("Logged in, current URL:", currentUrl);
    if (!currentUrl.includes('index.php')) {
        throw new Error("Failed to reach dashboard after login");
    }

    // 3. Find Logout button
    console.log("Testing Logout interaction...");
    const logoutBtnInfo = await evaluate(`(() => {
        const btn = document.querySelector('a[href="/admin/logout.php"]');
        if (!btn) return null;
        return {
            text: btn.innerText.trim(),
            hasConfirm: btn.getAttribute('data-confirm'),
            title: btn.getAttribute('data-confirm-title')
        };
    })()`);
    console.log("Logout button found:", logoutBtnInfo);

    // 4. Click logout button to trigger popup
    console.log("Clicking logout button to trigger confirmation modal...");
    await evaluate(`document.querySelector('a[href="/admin/logout.php"]').click()`);
    await new Promise(r => setTimeout(r, 600));

    // Check if modal is visible
    const modalVisible = await evaluate(`(() => {
        const modal = document.getElementById('modalGlobalConfirmDialog');
        return modal && modal.style.display !== 'none';
    })()`);
    console.log("Confirmation modal displayed:", modalVisible);
    if (!modalVisible) {
        throw new Error("Confirmation modal did not open!");
    }

    const modalTitle = await evaluate(`document.getElementById('globalConfirmTitle').innerText`);
    const modalMsg = await evaluate(`document.getElementById('globalConfirmMessage').innerText`);
    console.log("Modal Title:", modalTitle);
    console.log("Modal Message:", modalMsg);

    // 5. Click "Lanjut" (Proceed) button in confirmation modal
    console.log("Clicking 'Lanjut' button on modal...");
    await evaluate(`document.getElementById('btnGlobalConfirmProceed').click()`);
    await new Promise(r => setTimeout(r, 2000));

    // 6. Check URL after logout
    const postLogoutUrl = await evaluate(`window.location.href`);
    console.log("Post logout URL:", postLogoutUrl);
    if (!postLogoutUrl.includes('login.php')) {
        throw new Error("Browser did not navigate to login.php after confirming logout! Current URL: " + postLogoutUrl);
    }
    console.log("   [SUCCESS] Browser navigated to /admin/login.php!");

    // 7. Verify cannot go back to dashboard
    await send('Page.navigate', { url: 'http://127.0.0.1:8081/admin/index.php' });
    await new Promise(r => setTimeout(r, 1200));
    const recheckUrl = await evaluate(`window.location.href`);
    console.log("Recheck protected URL after logout:", recheckUrl);
    if (!recheckUrl.includes('login.php')) {
        throw new Error("Session still active after logout! Recheck failed.");
    }
    console.log("   [SUCCESS] Session is verified dead, redirect to login page enforced!");

    // Clean up
    ws.close();
    chrome.kill();
    console.log("=== ALL BROWSER LOGOUT TESTS COMPLETED SUCCESSFULLY! ===");
    process.exit(0);
}

run().catch(err => {
    console.error("FATAL TEST FAILURE:", err);
    process.exit(1);
});
