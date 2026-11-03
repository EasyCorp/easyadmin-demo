// Takes the screenshot of the listing of plants shown in the README, in the light and dark
// color schemes. Run it with "make screenshots" while the demo is being served. The screenshot
// is in English and it never shows the note about this being a demo application or the
// "View source code" button.

import { mkdir, writeFile } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';
import sharp from 'sharp';

const projectDir = resolve(dirname(fileURLToPath(import.meta.url)), '../..');
const baseUrl = (process.env.SCREENSHOTS_URL ?? 'https://127.0.0.1:8000').replace(/\/$/, '');
const outputDir = resolve(projectDir, process.env.SCREENSHOTS_DIR ?? 'doc/screenshots');

const pageUrl = `${baseUrl}/en/admin/plant`;
const colorSchemes = ['light', 'dark'];
const viewport = { width: 1440, height: 1080 };
// screenshots are twice as large as the viewport, so they look sharp on high density screens
const pixelRatio = 2;
const webpQuality = 90;
// the screenshot ends below this row, so the last row is never cut in half
const lastRowSelector = 'table.datagrid tbody tr[data-id]:nth-of-type(11)';

const hiddenElementsCss = `
    /* things that only make sense when browsing the demo */
    .demo-notice,
    .ea-sidebar-footer,
    [data-action-name='viewSource'],
    /* the web debug toolbar of Symfony */
    .sf-toolbar,
    .sf-minitoolbar,
    .sf-toolbarreset {
        display: none !important;
    }
`;

const browser = await chromium.launch();

try {
    await mkdir(outputDir, { recursive: true });

    for (const colorScheme of colorSchemes) {
        const context = await browser.newContext({
            colorScheme,
            deviceScaleFactor: pixelRatio,
            ignoreHTTPSErrors: true,
            locale: 'en-US',
            viewport,
        });
        // EasyAdmin stores in the browser the color scheme selected by the user
        await context.addInitScript((scheme) => window.localStorage.setItem('ea/colorScheme', scheme), colorScheme);

        const page = await context.newPage();
        const response = await page.goto(pageUrl, { waitUntil: 'networkidle' });
        if (null === response || !response.ok()) {
            throw new Error(`The "${pageUrl}" page returned a ${response?.status()} status code. Is the demo running on "${baseUrl}"?`);
        }
        await page.addStyleTag({ content: hiddenElementsCss });
        await page.evaluate(() => document.fonts.ready);

        const lastRow = await page.locator(lastRowSelector).boundingBox();
        if (null === lastRow) {
            throw new Error(`The "${lastRowSelector}" element was not found in the "${pageUrl}" page.`);
        }

        const image = await page.screenshot({
            clip: { x: 0, y: 0, width: viewport.width, height: lastRow.y + lastRow.height },
            type: 'png',
        });
        const file = resolve(outputDir, `index-${colorScheme}.webp`);
        await writeFile(file, await sharp(image).webp({ quality: webpQuality }).toBuffer());
        console.log(`saved ${file}`);

        await context.close();
    }
} finally {
    await browser.close();
}
