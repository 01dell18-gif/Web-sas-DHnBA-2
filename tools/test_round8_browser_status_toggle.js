const { spawn } = require('child_process');
const http = require('http');

const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const userDataDir = 'C:\\Users\\DKV-25\\.gemini\\antigravity-ide\\brain\\ab7e1379-e367-4ff0-9b4f-ecc973217e5a\\scratch\\chrome_test_profile_auto';

const chrome = spawn(chromePath, [
    '--headless=new',
    '--remote-debugging-port=9225',
    '--user-data-dir=' + userDataDir,
    '--disable-gpu',
    '--no-first-run',
    '--no-default-browser-check'
]);

function getJson(url) {
    return new Promise((resolve, reject) => {
        http.get(url, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => resolve(JSON.parse(data)));
        }).on('error', reject);
    });
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function run() {
    await sleep(1500);
    const pages = await getJson('http://127.0.0.1:9225/json');
    const targetPage = pages.find(p => p.type === 'page');
    if (!targetPage) {
        console.error('No page target found');
        chrome.kill();
        return;
    }

    const ws = new WebSocket(targetPage.webSocketDebuggerUrl);
    let id = 1;
    const callbacks = new Map();
    const exceptions = [];

    ws.onmessage = (event) => {
        const msg = JSON.parse(event.data);
        if (msg.method === 'Runtime.consoleAPICalled') {
            console.log('[BROWSER CONSOLE]', msg.params.type, msg.params.args.map(a => a.value || a.description).join(' '));
        }
        if (msg.method === 'Runtime.exceptionThrown') {
            console.error('[BROWSER EXCEPTION]', msg.params.exceptionDetails.text, msg.params.exceptionDetails.exception?.description);
            exceptions.push(msg.params.exceptionDetails);
        }
        if (msg.id && callbacks.has(msg.id)) {
            const cb = callbacks.get(msg.id);
            callbacks.delete(msg.id);
            if (msg.error) cb.reject(msg.error);
            else cb.resolve(msg.result);
        }
    };

    function send(method, params = {}) {
        return new Promise((resolve, reject) => {
            const msgId = id++;
            callbacks.set(msgId, { resolve, reject });
            ws.send(JSON.stringify({ id: msgId, method, params }));
        });
    }

    await new Promise(r => ws.onopen = r);
    await send('Runtime.enable');
    await send('Page.enable');

    async function evaluate(expression) {
        const res = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
        if (res.exceptionDetails) {
            console.error('Eval error:', res.exceptionDetails);
        }
        return res.result?.value;
    }

    // 1. Login
    console.log('Navigating to login...');
    await send('Page.navigate', { url: 'http://127.0.0.1:8080/admin/login.php' });
    await sleep(1200);

    await evaluate(`
        document.querySelector('input[name="username"]').value = 'admin';
        document.querySelector('input[name="password"]').value = 'admin123';
        document.querySelector('form').submit();
    `);
    await sleep(1500);

    // ==========================================
    // TEST 1: DATA KELAS
    // ==========================================
    console.log('\n--- TESTING DATA KELAS ---');
    await send('Page.navigate', { url: 'http://127.0.0.1:8080/admin/kelas.php' });
    await sleep(1500);

    let kInitial = await evaluate(`
        (() => {
            const pill = document.querySelector('.ceklis-status-pill');
            return {
                id: pill?.id,
                checked: pill.querySelector('input').checked,
                text: pill.querySelector('.ceklis-text').textContent.trim()
            };
        })()
    `);
    console.log('Kelas initial:', kInitial);

    // Click 1 (toggle to Nonaktif)
    console.log('Clicking kelas status pill (to Nonaktif)...');
    await evaluate(`document.querySelector('.ceklis-status-pill').click()`);
    await sleep(1200);

    let kAfter1 = await evaluate(`
        (() => {
            const pill = document.querySelector('.ceklis-status-pill');
            return {
                checked: pill.querySelector('input').checked,
                text: pill.querySelector('.ceklis-text').textContent.trim(),
                isInactive: pill.classList.contains('is-inactive'),
                toastShow: !!document.querySelector('.quick-status-toast.show')
            };
        })()
    `);
    console.log('Kelas after click 1 (expected Nonaktif):', kAfter1);

    // Click 2 (toggle back to Aktif)
    console.log('Clicking kelas status pill (back to Aktif)...');
    await evaluate(`document.querySelector('.ceklis-status-pill').click()`);
    await sleep(1200);

    let kAfter2 = await evaluate(`
        (() => {
            const pill = document.querySelector('.ceklis-status-pill');
            return {
                checked: pill.querySelector('input').checked,
                text: pill.querySelector('.ceklis-text').textContent.trim(),
                isActive: pill.classList.contains('is-active'),
                toastShow: !!document.querySelector('.quick-status-toast.show')
            };
        })()
    `);
    console.log('Kelas after click 2 (expected Aktif):', kAfter2);

    // ==========================================
    // TEST 2: KELOLA DATA SISWA
    // ==========================================
    console.log('\n--- TESTING KELOLA DATA SISWA ---');
    await send('Page.navigate', { url: 'http://127.0.0.1:8080/admin/siswa.php' });
    await sleep(1500);

    let sInitial = await evaluate(`
        (() => {
            const pill = document.querySelector('.ceklis-status-pill');
            return {
                id: pill?.id,
                checked: pill.querySelector('input').checked,
                text: pill.querySelector('.ceklis-text').textContent.trim()
            };
        })()
    `);
    console.log('Siswa initial:', sInitial);

    console.log('Clicking siswa status pill (to Nonaktif)...');
    await evaluate(`document.querySelector('.ceklis-status-pill').click()`);
    await sleep(1200);

    let sAfter1 = await evaluate(`
        (() => {
            const pill = document.querySelector('.ceklis-status-pill');
            return {
                checked: pill.querySelector('input').checked,
                text: pill.querySelector('.ceklis-text').textContent.trim(),
                isInactive: pill.classList.contains('is-inactive'),
                toastShow: !!document.querySelector('.quick-status-toast.show')
            };
        })()
    `);
    console.log('Siswa after click 1 (expected Nonaktif):', sAfter1);

    console.log('Clicking siswa status pill (back to Aktif)...');
    await evaluate(`document.querySelector('.ceklis-status-pill').click()`);
    await sleep(1200);

    let sAfter2 = await evaluate(`
        (() => {
            const pill = document.querySelector('.ceklis-status-pill');
            return {
                checked: pill.querySelector('input').checked,
                text: pill.querySelector('.ceklis-text').textContent.trim(),
                isActive: pill.classList.contains('is-active')
            };
        })()
    `);
    console.log('Siswa after click 2 (expected Aktif):', sAfter2);

    // ==========================================
    // TEST 3: DATA MAPEL
    // ==========================================
    console.log('\n--- TESTING DATA MAPEL ---');
    await send('Page.navigate', { url: 'http://127.0.0.1:8080/admin/mapel.php' });
    await sleep(1500);

    let mInitial = await evaluate(`
        (() => {
            const pill = document.querySelector('.ceklis-status-pill');
            return {
                id: pill?.id,
                checked: pill.querySelector('input').checked,
                text: pill.querySelector('.ceklis-text').textContent.trim()
            };
        })()
    `);
    console.log('Mapel initial:', mInitial);

    console.log('Clicking mapel status pill (to Nonaktif)...');
    await evaluate(`document.querySelector('.ceklis-status-pill').click()`);
    await sleep(1200);

    let mAfter1 = await evaluate(`
        (() => {
            const pill = document.querySelector('.ceklis-status-pill');
            return {
                checked: pill.querySelector('input').checked,
                text: pill.querySelector('.ceklis-text').textContent.trim(),
                isInactive: pill.classList.contains('is-inactive'),
                toastShow: !!document.querySelector('.quick-status-toast.show')
            };
        })()
    `);
    console.log('Mapel after click 1 (expected Nonaktif):', mAfter1);

    console.log('Clicking mapel status pill (back to Aktif)...');
    await evaluate(`document.querySelector('.ceklis-status-pill').click()`);
    await sleep(1200);

    let mAfter2 = await evaluate(`
        (() => {
            const pill = document.querySelector('.ceklis-status-pill');
            return {
                checked: pill.querySelector('input').checked,
                text: pill.querySelector('.ceklis-text').textContent.trim(),
                isActive: pill.classList.contains('is-active')
            };
        })()
    `);
    console.log('Mapel after click 2 (expected Aktif):', mAfter2);

    console.log('\nTotal exceptions caught:', exceptions.length);
    ws.close();
    chrome.kill();

    if (exceptions.length > 0) {
        console.error('FAILED: There were browser exceptions!');
        process.exit(1);
    }
    if (kAfter1.text !== 'Nonaktif' || kAfter2.text !== 'Aktif') {
        console.error('FAILED: Kelas toggle did not update correctly!');
        process.exit(1);
    }
    if (sAfter1.text !== 'Nonaktif' || sAfter2.text !== 'Aktif') {
        console.error('FAILED: Siswa toggle did not update correctly!');
        process.exit(1);
    }
    if (mAfter1.text !== 'Nonaktif' || mAfter2.text !== 'Aktif') {
        console.error('FAILED: Mapel toggle did not update correctly!');
        process.exit(1);
    }
    console.log('\n=== ALL BROWSER CDP TESTS PASSED WITH 0 EXCEPTIONS! ===');
    process.exit(0);
}

run().catch(e => {
    console.error('Fatal:', e);
    chrome.kill();
    process.exit(1);
});
