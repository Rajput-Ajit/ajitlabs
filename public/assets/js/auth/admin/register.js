
/**
 * auth/admin/register.js
 * Register + OTP flow
 * Same pattern as login.js
 */

document.addEventListener("DOMContentLoaded", () => {
  
  // ==============================
  // ELEMENTS
  // ==============================
  const form = document.getElementById("register-form");

  const registerBtn = document.getElementById("registerBtn");

  if (!form) return;

  // Email
  const emailInput = document.getElementById("emailInput");
  const emailSendBtn = document.getElementById("emailOtpBtn");
  const emailOtpWrap = document.getElementById("emailOtpWrap");
  const emailOtpInput = document.getElementById("emailOtpInput");
  const emailVerifyBtn = document.getElementById("emailVerifyBtn");

  // Mobile
  const mobileInput = document.getElementById("mobileInput");
  const mobileSendBtn = document.getElementById("mobileOtpBtn");
  const mobileOtpWrap = document.getElementById("mobileOtpWrap");
  const mobileOtpInput = document.getElementById("mobileOtpInput");
  const mobileVerifyBtn = document.getElementById("mobileVerifyBtn");

  // OTP status flags
  let isEmailVerified = false;
  let isMobileVerified = false;


  // ==============================
  // EMAIL SEND OTP
  // ==============================
  emailSendBtn?.addEventListener("click", async () => {

    const email = emailInput.value.trim();

    if (!email) {
      UI.toast.warning("Please enter email first.");
      emailInput.focus();
      return;
    }

    const oldText = Form.disable(emailSendBtn, "Sending...");

    try {
      /*
        EMAIL SEND OTP API
        Change URL below
      */
      const res = await Api.post("/app/api/send-email-otp.php", {
        email: email
      }, {
        auth: false,
        loader: true,
        toast: false
      });

      // show otp field
      emailOtpWrap.classList.remove("max-h-0", "opacity-0");
      emailOtpWrap.classList.add("max-h-40", "opacity-100");

      emailSendBtn.innerText = "Resend OTP";
      emailSendBtn.disabled = false;

      UI.toast.success(res.message || "OTP sent to email");

    } catch (err) {
      Form.enable(emailSendBtn, oldText);
    }
  });


  // ==============================
  // EMAIL VERIFY OTP
  // ==============================
  emailVerifyBtn?.addEventListener("click", async () => {

    const email = emailInput.value.trim();
    const otp = emailOtpInput.value.trim();

    if (!otp) {
      UI.toast.warning("Enter email OTP.");
      emailOtpInput.focus();
      return;
    }

    const oldText = Form.disable(emailVerifyBtn, "Verifying...");

    try {
      /*
        EMAIL VERIFY OTP API
      */
      const res = await Api.post("/app/api/verify-email-otp.php", {
        email: email,
        otp: otp
      }, {
        auth: false,
        loader: true,
        toast: false
      });

      isEmailVerified = true;

      emailVerifyBtn.innerText = "Verified ✓";
      emailVerifyBtn.disabled = true;
      emailOtpInput.classList.add("border-green-500");

      UI.toast.success(res.message || "Email verified");

    } catch (err) {
      Form.enable(emailVerifyBtn, oldText);
    }
  });


  // ==============================
  // MOBILE SEND OTP
  // ==============================
  mobileSendBtn?.addEventListener("click", async () => {

    const mobile = mobileInput.value.trim();

    if (!mobile) {
      UI.toast.warning("Please enter mobile number first.");
      mobileInput.focus();
      return;
    }

    const oldText = Form.disable(mobileSendBtn, "Sending...");

    try {
      /*
        MOBILE SEND OTP API
      */
      const res = await Api.post("/app/api/send-mobile-otp.php", {
        mobile: mobile
      }, {
        auth: false,
        loader: true,
        toast: false
      });

      mobileOtpWrap.classList.remove("max-h-0", "opacity-0");
      mobileOtpWrap.classList.add("max-h-40", "opacity-100");

      mobileSendBtn.innerText = "Resend OTP";
      mobileSendBtn.disabled = false;

      UI.toast.success(res.message || "OTP sent to mobile");

    } catch (err) {
      Form.enable(mobileSendBtn, oldText);
    }
  });


  // ==============================
  // MOBILE VERIFY OTP
  // ==============================
  mobileVerifyBtn?.addEventListener("click", async () => {

    const mobile = mobileInput.value.trim();
    const otp = mobileOtpInput.value.trim();

    if (!otp) {
      UI.toast.warning("Enter mobile OTP.");
      mobileOtpInput.focus();
      return;
    }

    const oldText = Form.disable(mobileVerifyBtn, "Verifying...");

    try {
      /*
        MOBILE VERIFY OTP API
      */
      const res = await Api.post("/app/api/verify-mobile-otp.php", {
        mobile: mobile,
        otp: otp
      }, {
        auth: false,
        loader: true,
        toast: false
      });

      isMobileVerified = true;

      mobileVerifyBtn.innerText = "Verified ✓";
      mobileVerifyBtn.disabled = true;
      mobileOtpInput.classList.add("border-green-500");

      UI.toast.success(res.message || "Mobile verified");

    } catch (err) {
      Form.enable(mobileVerifyBtn, oldText);
    }
  });


  // ==============================
  // FINAL REGISTER
  // ==============================
  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    Form.clearErrors(form);

    const body = Form.serialize(form);

    // basic checks
    if (!body.first_name || !body.last_name || !body.email || !body.mobile || !body.password) {
      UI.toast.warning("Please fill all required fields.");
      return;
    }

    if (!isEmailVerified) {
      UI.toast.warning("Please verify your email first.");
      return;
    }

    if (!isMobileVerified) {
      UI.toast.warning("Please verify your mobile first.");
      return;
    }

    const oldText = Form.disable(registerBtn, "Creating Account...");

    try {
      /*
        FINAL REGISTER API
      */
      const res = await Api.post("/app/api/admin.register.php", body, {
        auth: false,
        loader: true,
        toast: false
      });

      UI.toast.success(res.message || "Registration successful!");

      // redirect after success
      setTimeout(() => {
        window.location.href =
          Api.CONFIG.baseUrl + "/public/admin/admin-dashboard.php";
      }, 1000);

    } catch (err) {
      Form.enable(registerBtn, oldText);
    }
  });

});