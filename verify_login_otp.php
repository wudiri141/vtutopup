<?php
session_start();

if (!isset($_SESSION['otp_user_id'])) {
  header("Location: login.php?error=Login session expired.");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify OTP</title>

<link rel="stylesheet" href="styless.css">

<style>

.otp-box{
display:flex;
justify-content:center;
gap:10px;
margin:20px 0;
}

.otp-box input{
width:45px;
height:55px;
font-size:22px;
text-align:center;
border:1px solid #ccc;
border-radius:8px;
outline:none;
}

.otp-box input:focus{
border-color:#4CAF50;
}

</style>

</head>
<body>

<div class="container">

<h1 class="form-title">Enter OTP</h1>
<p class="form-sub">Enter the 4-digit code sent to your email</p>

<?php if (isset($_GET['error'])): ?>
<div style="color:red;text-align:center;">
<?= htmlspecialchars($_GET['error']) ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
<div style="color:green;text-align:center;">
<?= htmlspecialchars($_GET['success']) ?>
</div>
<?php endif; ?>

<form action="verify_login_otp_process.php" method="POST" id="otpForm">

<div class="otp-box">
<input type="text" maxlength="1" class="otp-input">
<input type="text" maxlength="1" class="otp-input">
<input type="text" maxlength="1" class="otp-input">
<input type="text" maxlength="1" class="otp-input">
</div>

<input type="hidden" name="otp" id="otpValue">

<button type="submit" class="btn-submit">Verify OTP</button>

</form>

<p style="text-align:center;margin-top:15px;">
<a href="resend_login_otp.php">Resend OTP</a>
</p>

</div>

<script>

const inputs = document.querySelectorAll(".otp-input");
const hidden = document.getElementById("otpValue");

inputs.forEach((input,index)=>{

input.addEventListener("input",function(){

if(this.value.length===1 && index < inputs.length-1){
inputs[index+1].focus();
}

updateOTP();

});

input.addEventListener("keydown",function(e){

if(e.key==="Backspace" && this.value==="" && index>0){
inputs[index-1].focus();
}

});

});

function updateOTP(){

let otp="";

inputs.forEach(input=>{
otp += input.value;
});

hidden.value = otp;

}

inputs.forEach(input => {
  input.addEventListener("input", function(){
    if (hidden.value.length === 4) {
      document.getElementById("otpForm").submit();
    }
  });
});
</script>

</body>
</html>
