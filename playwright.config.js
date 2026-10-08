import { defineConfig } from '@playwright/test';
import path from 'node:path';
export default defineConfig({
 testDir: './tests/browser', fullyParallel: false, workers: 1, timeout: 45000,
 reporter: [['list'],['html',{open:'never'}]],
 use: {baseURL:process.env.E2E_BASE_URL||'http://127.0.0.1:8000',headless:true,viewport:{width:1440,height:1000},screenshot:'only-on-failure',trace:'retain-on-failure',launchOptions:{args:['--use-fake-ui-for-media-stream','--use-fake-device-for-media-stream','--use-file-for-fake-video-capture='+path.resolve('.qa/demo-camera.y4m')]}}
});
