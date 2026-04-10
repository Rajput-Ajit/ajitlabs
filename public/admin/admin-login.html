<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ReadSpace – Admin Login</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  body{font-family:'DM Sans',sans-serif;background:#0e0e22;color:#e2e8f0}
  .display{font-family:'Playfair Display',serif}
  .gold{background:linear-gradient(135deg,#c9a84c,#e8b84b)}
  .glass{background:rgba(255,255,255,0.04);backdrop-filter:blur(20px);border:1px solid rgba(201,168,76,0.18)}
  .field{background:rgba(255,255,255,0.05);border:1px solid rgba(201,168,76,0.22);color:#e2e8f0;width:100%;padding:12px 16px;border-radius:12px;font-size:14px;transition:border-color 0.2s,box-shadow 0.2s}
  .field:focus{outline:none;border-color:#c9a84c;box-shadow:0 0 0 3px rgba(201,168,76,0.12)}
  .field::placeholder{color:rgba(255,255,255,0.28)}
  .field-select{background:rgba(255,255,255,0.05);border:1px solid rgba(201,168,76,0.22);color:#e2e8f0}
  .field-select option{background:#1a1a35;color:#e2e8f0}
  .btn{background:linear-gradient(135deg,#c9a84c,#e8b84b);color:#12122a;font-weight:700;width:100%;padding:13px;border-radius:12px;font-size:14px;cursor:pointer;transition:all 0.2s;-webkit-tap-highlight-color:transparent}
  .btn:active{opacity:0.9;transform:scale(0.99)}
  .tab-a{border-bottom:2px solid #c9a84c;color:#e8b84b;font-weight:600}
  .tab-i{border-bottom:2px solid transparent;color:#64748b}
  .fade{animation:fi 0.4s ease forwards}
  @keyframes fi{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
  .pat{background-image:repeating-linear-gradient(45deg,transparent,transparent 40px,rgba(201,168,76,0.025) 40px,rgba(201,168,76,0.025) 80px)}
</style>
</head>
<body class="min-h-screen pat flex items-start justify-center pt-8 pb-12 px-4">

<!-- Glow orbs -->
<div class="fixed top-0 left-0 w-72 h-72 rounded-full opacity-[0.07] pointer-events-none" style="background:radial-gradient(circle,#c9a84c,transparent);transform:translate(-40%,-40%)"></div>
<div class="fixed bottom-0 right-0 w-56 h-56 rounded-full opacity-[0.06] pointer-events-none" style="background:radial-gradient(circle,#e8b84b,transparent);transform:translate(40%,40%)"></div>

<div class="w-full max-w-sm sm:max-w-md">
  <!-- Logo -->
  <div class="text-center mb-7">
    <div class="inline-flex items-center gap-3 mb-2">
      <div class="w-10 h-10 gold rounded-xl flex items-center justify-center text-xl font-black text-stone-900">R</div>
      <span class="display text-3xl text-amber-300 font-bold">ReadSpace</span>
    </div>
    <p class="text-stone-500 text-xs tracking-widest uppercase">Reading Hall Management System</p>
  </div>

  <!-- Card -->
  <div class="glass rounded-2xl p-6 sm:p-8">
    <!-- Tabs -->
    <div class="flex mb-7 border-b border-white/10">
      <button onclick="showTab('login')" id="tl" class="tab-a flex-1 pb-3 text-sm transition-all touch-manipulation">Sign In</button>
      <button onclick="showTab('reg')" id="tr" class="tab-i flex-1 pb-3 text-sm transition-all touch-manipulation">Register Hall</button>
    </div>

    <!-- LOGIN -->
    <div id="fl" class="fade space-y-4">
    <form id="login-form" method="post">
      <div>
        <div class="display text-2xl text-white font-bold mb-1">Welcome back</div>
        <p class="text-stone-400 text-sm mb-5">Sign in to manage your reading halls</p>
      </div>
      <div>
        <label class="text-xs text-stone-400 uppercase tracking-wider mb-1.5 block">Email Address</label>
        <input type="email" placeholder="admin@readspace.com" class="field" name="email">
      </div>
      <div>
        <label class="text-xs text-stone-400 uppercase tracking-wider mb-1.5 block">Password</label>
        <div class="relative">
          <input type="password" id="pw" placeholder="••••••••" class="field pr-12" name="password">
          <button onclick="togglePw()" class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 hover:text-amber-400 text-xl w-8 h-8 flex items-center justify-center">👁</button>
        </div>
      </div>
      <div class="flex items-center justify-between flex-wrap gap-2 pt-1">
        <label class="flex items-center gap-2 text-xs text-stone-400 cursor-pointer select-none">
          <input type="checkbox" class="accent-amber-400 w-4 h-4 rounded"> Remember me
        </label>
        <a href="#" class="text-xs text-amber-400 hover:underline">Forgot password?</a>
      </div>
      <button class="btn mt-1">Sign In to Admin Panel →</button>
    </form>  
      <p class="text-center text-stone-500 text-xs mt-2">No account? <button onclick="showTab('reg')" class="text-amber-400 hover:underline">Register your hall</button></p>
    </div>

    <!-- REGISTER -->
    <form id="register-form" method="POST">
      <div id="fr" class="hidden fade space-y-4">

        <!-- Heading -->
        <div>
          <div class="display text-2xl text-white font-bold mb-1">Register Your Hall</div>
          <p class="text-stone-400 text-sm mb-4">Set up your reading hall on ReadSpace</p>
        </div>

        <!-- Name -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <input type="text" placeholder="First Name" class="field w-full" name="first_name">
          <input type="text" placeholder="Last Name" class="field w-full" name="last_name">
        </div>

        <!-- Hall -->
        <input type="text" placeholder="Reading Hall Name" class="field w-full" name="reading_name">

        <!-- EMAIL -->
        <div>
          <div class="flex flex-col sm:flex-row gap-2">
            <input type="email" id="emailInput" placeholder="Email Address" class="field w-full" name="email">
            <button type="button" id="emailOtpBtn" class="btn w-full sm:w-auto">Send OTP</button>
          </div>

          <div id="emailOtpWrap"
            class="mt-3 overflow-hidden max-h-0 opacity-0 transition-all duration-500">

            <div class="flex flex-col sm:flex-row gap-2">
              <input type="text" id="emailOtpInput" maxlength="6" placeholder="Enter Email OTP" class="field w-full" name="email_otp">
              <button type="button" id="emailVerifyBtn" class="btn w-full sm:w-auto">Verify</button>
            </div>

          </div>
        </div>

        <!-- MOBILE -->
        <div>
          <div class="flex flex-col sm:flex-row gap-2">
            <input type="tel" id="mobileInput" placeholder="+91 98765 43210" class="field w-full" name="mobile">
            <button type="button" id="mobileOtpBtn" class="btn w-full sm:w-auto">Send OTP</button>
          </div>

          <div id="mobileOtpWrap"
            class="mt-3 overflow-hidden max-h-0 opacity-0 transition-all duration-500">

            <!-- NOTE -->
            <div class="mb-3 text-[11px] text-amber-400 bg-stone-800/40 border border-stone-700 rounded-xl px-3 py-2">
              SMS OTP under development. Use default OTP:
              <span class="text-white font-semibold">000000</span>
            </div>

            <div class="flex flex-col sm:flex-row gap-2">
              <input type="text" id="mobileOtpInput" maxlength="6" placeholder="Enter Mobile OTP" class="field w-full" name="mobile_otp">
              <button type="button" id="mobileVerifyBtn" class="btn w-full sm:w-auto">Verify</button>
            </div>

          </div>
        </div>

        <!-- City -->
        <input type="text" placeholder="City / Branch" class="field w-full" name="city">

        <!-- Password -->
        <input type="password" placeholder="Password" class="field w-full" name="password">

        <button class="btn w-full">Create Account →</button>

      </div>
    </form>
  </div>

  <!-- Trust badges -->
  <div class="flex flex-wrap gap-2 mt-4 justify-center">
    <span class="text-xs text-stone-500 px-3 py-1.5 glass rounded-full">🔒 256-bit SSL</span>
    <span class="text-xs text-stone-500 px-3 py-1.5 glass rounded-full">✓ 14-day free trial</span>
    <span class="text-xs text-stone-500 px-3 py-1.5 glass rounded-full">📞 24/7 support</span>
  </div>
</div>

<script>
function showTab(t) {
  const isLogin = t === 'login';
  document.getElementById('fl').classList.toggle('hidden', !isLogin);
  document.getElementById('fr').classList.toggle('hidden', isLogin);
  document.getElementById('tl').className = isLogin ? 'tab-a flex-1 pb-3 text-sm transition-all touch-manipulation' : 'tab-i flex-1 pb-3 text-sm transition-all touch-manipulation';
  document.getElementById('tr').className = !isLogin ? 'tab-a flex-1 pb-3 text-sm transition-all touch-manipulation' : 'tab-i flex-1 pb-3 text-sm transition-all touch-manipulation';
}
function togglePw() {
  const p = document.getElementById('pw');
  p.type = p.type === 'password' ? 'text' : 'password';
}
</script>
<script src="../assets/js/core/ui.js"></script>
<script src="../assets/js/core/api.js"></script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/core/helpers.js"></script>
<script src="../assets/js/auth/admin/login.js"></script>
<script src="../assets/js/auth/admin/register.js"></script>
</body>
</html>
