/* ===== Helper functions ===== */
const $ = id => document.getElementById(id);
const form = $('regForm');

// Show or clear an error message under a field
function setState(input, msg){
  const f = input.closest('.field');
  f.classList.toggle('invalid', !!msg);
  f.classList.toggle('valid', !msg);
  f.querySelector('.error').textContent = msg || '';
  return !msg;
}

// Password strength score 0-5: length, lower, upper, digit, symbol
function strength(p){
  return [p.length >= 8, /[a-z]/.test(p), /[A-Z]/.test(p), /\d/.test(p), /[^A-Za-z0-9]/.test(p)]
    .filter(Boolean).length;
}

/* ===== Individual validators (return error text or '') ===== */
const rules = {
  name: v => !v.trim() ? 'Name is required.' :
             !/^[A-Za-z][A-Za-z .'-]{2,}$/.test(v.trim()) ? 'Enter a valid name (letters only, min 3 chars).' : '',
  roll: v => !v.trim() ? 'Roll number is required.' :
             !/^[A-Za-z0-9\/-]{5,15}$/.test(v.trim()) ? 'Roll number must be 5–15 letters/digits.' : '',
  email: v => !v.trim() ? 'Email is required.' :
              !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v.trim()) ? 'Enter a valid email address.' : '',
  course: v => !v ? 'Please select a course.' : '',
  password: v => !v ? 'Password is required.' :
                 strength(v) < 4 || v.length < 8 ? 'Password too weak: need 8+ chars with upper, lower, number & symbol.' : '',
  confirm: v => !v ? 'Please confirm your password.' :
                v !== $('password').value ? 'Passwords do not match.' : '',
  file: () => {
    const f = $('file').files[0];
    if(!f) return 'Please upload a file.';
    if(!/\.(pdf|jpe?g|png)$/i.test(f.name)) return 'Only PDF, JPG or PNG allowed.';
    if(f.size > 2*1024*1024) return 'File must be smaller than 2 MB.';
    return '';
  }
};

function validate(id){
  const el = $(id);
  return setState(el, rules[id](el.value));
}

/* ===== Live validation: check on blur, and re-check on input once touched ===== */
Object.keys(rules).forEach(id=>{
  const el = $(id);
  el.addEventListener('blur', ()=>validate(id));
  el.addEventListener(id==='course'||id==='file' ? 'change' : 'input', ()=>{
    if(el.closest('.field').classList.contains('invalid') || id==='password') validate(id);
    if(id==='password' && $('confirm').value) validate('confirm');
  });
});

/* ===== Password strength meter ===== */
$('password').addEventListener('input', e=>{
  const s = strength(e.target.value), bar = $('bar');
  const labels = ['','Very weak','Weak','Fair','Strong','Very strong'];
  const colors = ['','#dc2626','#f97316','#eab308','#22c55e','#16a34a'];
  bar.style.width = (s*20)+'%'; bar.style.background = colors[s];
  $('strengthText').textContent = e.target.value ? 'Strength: '+labels[s] : 'Use 8+ chars, upper, lower, number & symbol';
});

/* ===== Show / hide password ===== */
document.querySelectorAll('.toggle').forEach(b=>b.addEventListener('click', ()=>{
  const t = $(b.dataset.target);
  t.type = t.type === 'password' ? 'text' : 'password';
}));

/* ===== Submit: validate everything, focus first error ===== */
form.addEventListener('submit', e=>{
  e.preventDefault();
  const results = Object.keys(rules).map(id=>[id, validate(id)]);
  const bad = results.find(r=>!r[1]);
  if(bad){ $(bad[0]).focus(); return; }
  // All valid → in a real app, POST to PHP/Servlet here with FormData(form)
  $('summary').innerHTML = [
    ['Name',$('name').value.trim()],['Roll No.',$('roll').value.trim()],
    ['Email',$('email').value.trim()],['Course',$('course').value],
    ['File',$('file').files[0].name]
  ].map(r=>`<tr><td>${r[0]}</td><td></td></tr>`).join('');
  [...$('summary').rows].forEach((row,i)=>{
    row.cells[1].textContent = [$('name').value.trim(),$('roll').value.trim(),$('email').value.trim(),$('course').value,$('file').files[0].name][i];
  });
  form.style.display='none'; $('success').style.display='block';
});

/* ===== Reset helpers ===== */
function clearAll(){
  document.querySelectorAll('.field').forEach(f=>f.classList.remove('invalid','valid'));
  $('bar').style.width='0'; $('strengthText').textContent='Use 8+ chars, upper, lower, number & symbol';
}
$('resetBtn').addEventListener('click', clearAll);
$('again').addEventListener('click', ()=>{
  form.reset(); clearAll(); $('success').style.display='none'; form.style.display='block';
});
