import { createIcons, icons } from 'lucide';
createIcons({ icons, attrs: { 'aria-hidden': 'true' } });
const menuButton = document.querySelector('[data-menu-toggle]');
function closeMenu() { document.body.classList.remove('menu-open'); menuButton?.setAttribute('aria-expanded','false'); }
menuButton?.addEventListener('click',()=>{const open=document.body.classList.toggle('menu-open');menuButton.setAttribute('aria-expanded',String(open));});
document.querySelector('[data-menu-close]')?.addEventListener('click',closeMenu);
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeMenu();});
document.querySelectorAll('[data-dismiss]').forEach(button=>button.addEventListener('click',()=>button.closest('.alert').remove()));
document.querySelectorAll('[data-password-toggle]').forEach(button=>button.addEventListener('click',()=>{const input=button.parentElement.querySelector('input');input.type=input.type==='password'?'text':'password';button.setAttribute('aria-label',input.type==='password'?'Tampilkan password':'Sembunyikan password');}));
document.querySelectorAll('[data-print]').forEach(button=>button.addEventListener('click',()=>window.print()));
let confirmedForm=null;
const dialog=document.getElementById('confirm-dialog');
document.querySelectorAll('form').forEach(form=>form.addEventListener('submit',event=>{
 if(form.dataset.confirm && !form.dataset.confirmed){event.preventDefault();confirmedForm=form;document.getElementById('confirm-message').textContent=form.dataset.confirm;dialog.showModal();return;}
 const button=event.submitter;if(button){button.disabled=true;button.setAttribute('aria-busy','true');if(!button.querySelector('svg'))button.textContent='Memproses…';}
}));
document.getElementById('confirm-cancel')?.addEventListener('click',()=>dialog.close());
document.getElementById('confirm-yes')?.addEventListener('click',()=>{dialog.close();if(confirmedForm){confirmedForm.dataset.confirmed='true';confirmedForm.requestSubmit();}});
window.addEventListener('pageshow',()=>document.querySelectorAll('[aria-busy=true]').forEach(b=>{b.disabled=false;b.removeAttribute('aria-busy');}));
const clock=document.querySelector('[data-clock]');
if(clock){const update=()=>clock.textContent=new Intl.DateTimeFormat('id-ID',{timeZone:'Asia/Jakarta',hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false}).format(new Date()).replaceAll('.',':');update();setInterval(update,1000);}
const absenceType=document.getElementById('type');
function updateEvidence(){const evidence=document.getElementById('evidence');if(!evidence||!absenceType)return;evidence.required=absenceType.value==='SAKIT'&&evidence.dataset.sickRequired==='1';const purpose=document.getElementById('purpose');if(purpose)purpose.required=absenceType.value==='IZIN';}
absenceType?.addEventListener('change',updateEvidence);updateEvidence();
const chartData=document.getElementById('dashboard-chart-data');
if(chartData){import('chart.js/auto').then(({default:Chart})=>{
 const data=JSON.parse(chartData.textContent);const colors=['#5f9c7e','#dfb75c','#91b6d4','#b4a7ce','#d39a8a'];
 Chart.defaults.font.family="'Segoe UI',system-ui,sans-serif";Chart.defaults.color='#8c9b91';Chart.defaults.font.size=10;
 const common={responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false},border:{display:false}},y:{beginAtZero:true,ticks:{precision:0},grid:{color:'#f0f3ef'},border:{display:false}}}};
 if(document.getElementById('weekly-chart'))new Chart(document.getElementById('weekly-chart'),{type:'bar',data:{labels:data.weekly.labels,datasets:[{label:'Hadir',data:data.weekly.hadir,backgroundColor:'#88bba0',borderRadius:4,maxBarThickness:23},{label:'Terlambat',data:data.weekly.terlambat,backgroundColor:'#e9d8a9',borderRadius:4,maxBarThickness:23}]},options:common});
 if(document.getElementById('status-chart'))new Chart(document.getElementById('status-chart'),{type:'doughnut',data:{labels:Object.keys(data.monthStats),datasets:[{data:Object.values(data.monthStats),backgroundColor:colors,borderWidth:4,borderColor:'#fff',hoverOffset:3}]},options:{responsive:true,maintainAspectRatio:false,cutout:'77%',plugins:{legend:{display:false}}}});
 if(document.getElementById('monthly-chart'))new Chart(document.getElementById('monthly-chart'),{type:'line',data:{labels:data.monthly.labels,datasets:[{label:'Hadir + terlambat',data:data.monthly.values,borderColor:'#439b80',backgroundColor:'#e7f3ec',fill:true,tension:.3,pointRadius:2,borderWidth:2}]},options:common});
});}
const scannerPage=document.getElementById('scanner-page');
if(scannerPage){let scanner=null,busy=false,running=false,last='',lastAt=0;const result=document.getElementById('scan-result'),start=document.getElementById('start-camera'),stop=document.getElementById('stop-camera'),placeholder=document.getElementById('scanner-placeholder');
 const output=(title,lines,success=false)=>{result.replaceChildren();const h=document.createElement('h2');h.textContent=title;result.append(h);for(const line of lines){const p=document.createElement('p');p.className='mt-2 text-sm';p.textContent=line;result.append(p);}result.classList.toggle('success',success);};
 const decoded=async qr=>{const type=document.querySelector('input[name=scan_type]:checked').value,key=type+qr;if(busy||(key===last&&Date.now()-lastAt<8000))return;busy=true;last=key;lastAt=Date.now();output('Memvalidasi QR…',[]);
 try{const response=await fetch(scannerPage.dataset.endpoint,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:JSON.stringify({qr,type})});const data=await response.json();if(!response.ok){const messages=Object.values(data.errors||{}).flat();throw new Error(messages[0]||(response.status===419?'Sesi berakhir. Muat ulang halaman.':'Absensi tidak dapat disimpan.'));}output(data.message,[data.name,data.identity,data.date+' · '+data.time,'Jenis: '+data.type,'Status: '+data.status],true);}catch(error){output('Absensi belum tersimpan',[error.message]);}finally{busy=false;}};
 const getScanner=async()=>{if(!scanner){const {Html5Qrcode}=await import('html5-qrcode');scanner=new Html5Qrcode('qr-reader',{verbose:false});}return scanner;};
 start.addEventListener('click',async()=>{start.disabled=true;try{if(!window.isSecureContext)throw new Error('Kamera membutuhkan HTTPS atau localhost.');await getScanner();placeholder.hidden=true;await scanner.start({facingMode:'environment'},{fps:10,qrbox:{width:210,height:210}},decoded,()=>{});running=true;stop.disabled=false;output('Kamera siap',['Arahkan QR kartu ke dalam bingkai.']);}catch(error){placeholder.hidden=false;start.disabled=false;output('Kamera tidak dapat digunakan.',['Gunakan absensi manual atau periksa permission kamera.',error.message||'']);}});
 stop.addEventListener('click',async()=>{if(running){await scanner.stop();running=false;}stop.disabled=true;start.disabled=false;placeholder.hidden=false;});
 document.getElementById('scan-file').addEventListener('change',async event=>{const file=event.target.files[0];if(!file)return;try{await getScanner();if(running){await scanner.stop();running=false;stop.disabled=true;start.disabled=false;}const qr=await scanner.scanFile(file,false);await decoded(qr);}catch{output('QR Code tidak terbaca.',['Pilih foto QR yang tajam dan utuh.']);}event.target.value='';});
 window.addEventListener('pagehide',()=>{if(running)scanner.stop().catch(()=>{});});
}
