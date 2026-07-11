<!-- PIN MODAL (Reusable) -->
<style>
  .pin-backdrop{
    position:fixed; inset:0; display:none;
    background:rgba(15,23,42,.55);
    align-items:center; justify-content:center;
    z-index:9999;
    padding:18px;
  }
  .pin-modal{
    width:min(420px, 100%);
    background:#fff;
    border-radius:18px;
    border:1px solid #e5e7eb;
    box-shadow:0 18px 60px rgba(0,0,0,.2);
    overflow:hidden;
  }
  .pin-head{
    padding:14px 16px;
    background:#1E9BD7;
    color:#fff;
    display:flex; align-items:center; justify-content:space-between;
    gap:10px;
  }
  .pin-head b{font-size:14px}
  .pin-close{
    width:34px;height:34px;border-radius:10px;
    border:1px solid rgba(255,255,255,.25);
    background:rgba(255,255,255,.12);
    color:#fff; cursor:pointer;
  }
  .pin-body{ padding:16px; }
  .pin-body p{ margin:0 0 10px; color:#6b7280; font-size:13px; line-height:1.5; }
  .pin-input{
    width:100%;
    padding:14px 12px;
    border:1px solid #e5e7eb;
    border-radius:14px;
    font-size:18px;
    text-align:center;
    letter-spacing:10px;
    outline:none;
  }
  .pin-row{
    display:flex; gap:10px; margin-top:12px;
  }
  .pin-btn{
    flex:1;
    padding:12px 14px;
    border-radius:14px;
    border:1px solid #e5e7eb;
    background:#fff;
    font-weight:800;
    cursor:pointer;
  }
  .pin-btn.primary{
    background:#1E9BD7;
    border-color:#1E9BD7;
    color:#fff;
  }
  .pin-err{
    margin-top:10px;
    display:none;
    background:#fee2e2;
    border:1px solid #fecaca;
    color:#991b1b;
    padding:10px;
    border-radius:12px;
    font-size:13px;
    font-weight:700;
  }
</style>

<div class="pin-backdrop" id="pinBackdrop">
  <div class="pin-modal">
    <div class="pin-head">
      <b id="pinTitle">Confirm Transaction PIN</b>
      <button class="pin-close" type="button" onclick="PinModal.close()">✕</button>
    </div>

    <div class="pin-body">
      <p id="pinSub">Enter your 4-digit PIN to continue.</p>

      <input
        class="pin-input"
        id="pinValue"
        type="password"
        inputmode="numeric"
        maxlength="4"
        placeholder="••••"
        autocomplete="off"
      />

      <div class="pin-err" id="pinErr">PIN must be exactly 4 digits.</div>

      <div class="pin-row">
        <button class="pin-btn" type="button" onclick="PinModal.close()">Cancel</button>
        <button class="pin-btn primary" type="button" onclick="PinModal.confirm()">Confirm</button>
      </div>
    </div>
  </div>
</div>

<script>
const PinModal = (function(){
  let currentForm = null;
  let hiddenPinInput = null;

  const backdrop = () => document.getElementById('pinBackdrop');
  const pinEl = () => document.getElementById('pinValue');
  const errEl = () => document.getElementById('pinErr');
  const titleEl = () => document.getElementById('pinTitle');
  const subEl = () => document.getElementById('pinSub');

  function open(form, opts={}){
    currentForm = form;

    hiddenPinInput = currentForm.querySelector('input[name="pin"]');
    if(!hiddenPinInput){
      hiddenPinInput = document.createElement('input');
      hiddenPinInput.type = 'hidden';
      hiddenPinInput.name = 'pin';
      currentForm.appendChild(hiddenPinInput);
    }

    titleEl().textContent = opts.title || 'Confirm Transaction PIN';
    subEl().textContent = opts.sub || 'Enter your 4-digit PIN to continue.';
    errEl().style.display = 'none';
    pinEl().value = '';

    backdrop().style.display = 'flex';
    setTimeout(()=>pinEl().focus(), 50);
  }

  function close(){
    backdrop().style.display = 'none';
    currentForm = null;
    hiddenPinInput = null;
  }

  function confirm(){
    const v = (pinEl().value || '').trim();
    if(!/^\d{4}$/.test(v)){
      errEl().style.display = 'block';
      return;
    }
    errEl().style.display = 'none';
    hiddenPinInput.value = v;

    const f = currentForm;
    close();
    f.submit();
  }

  function bind(formSelector, opts={}){
    const form = document.querySelector(formSelector);
    if(!form) return;

    form.addEventListener('submit', function(ev){
      // if pin already exists and valid, allow
      const existing = form.querySelector('input[name="pin"]');
      if(existing && existing.value && /^\d{4}$/.test(existing.value)) return;

      ev.preventDefault();
      open(form, opts);
    });
  }

  // click outside closes
  document.addEventListener('click', (e)=>{
    const bd = backdrop();
    if(bd && bd.style.display === 'flex' && e.target === bd) close();
  });

  // enter confirms, esc closes
  document.addEventListener('keydown', (e)=>{
    const bd = backdrop();
    if(!bd || bd.style.display !== 'flex') return;

    if(e.key === 'Enter'){
      e.preventDefault();
      confirm();
    }
    if(e.key === 'Escape'){
      close();
    }
  });

  return { open, close, confirm, bind };
})();
</script>
