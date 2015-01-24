<?PHP
require_once("./include/membersite_config.php");

if(isset($_POST['submitted']) && $_POST['submitted'])
{
   if($fgmembersite->RegisterUser())
   {
        $fgmembersite->RedirectToURL("thank-you.html");
   }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" lang="en-US">
<head>
    <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
    <title>Register</title>
    <link rel="STYLESHEET" type="text/css" href="style/fg_membersite.css" />
    <link rel="STYLESHEET" type="text/css" href="style/pwdwidget.css" />
</head>
<body>

<!-- Form Code Start -->
<div id='fg_membersite'>
<form id='register' action='<?= $fgmembersite->GetSelfScript(); ?>' method='post' accept-charset='UTF-8'>
<fieldset >
<legend>Register</legend>

<input type='hidden' name='submitted' id='submitted' value='1'/>

<div class='short_explanation'>* required fields</div>
<input type='text'  class='spmhidip' name='<?= $fgmembersite->GetSpamTrapInputName(); ?>' />

<div><span class='error'><?= $fgmembersite->GetErrorMessage(); ?></span></div>
<div class='container'>
    <label for='first_name' >First Name*: </label><br/>
    <input type='text' name='first_name' id='first_name' value='<?= $fgmembersite->SafeDisplay('first_name') ?>' maxlength="50" /><br/>
    <span id='register_first_name_errorloc' class='error'></span>
</div>
<div class='container'>
    <label for='last_name' >Last Name*: </label><br/>
    <input type='text' name='last_name' id='last_name' value='<?= $fgmembersite->SafeDisplay('last_name') ?>' maxlength="50" /><br/>
    <span id='register_last_name_errorloc' class='error'></span>
</div>
<div class='container'>
    <label for='email' >Email Address*:</label><br/>
    <input type='text' name='email' id='email' value='<?= $fgmembersite->SafeDisplay('email') ?>' maxlength="50" /><br/>
    <span id='register_email_errorloc' class='error'></span>
</div>
<div class='container'>
    <label for='username' >UserName*:</label><br/>
    <input type='text' name='username' id='username' value='<?= $fgmembersite->SafeDisplay('username') ?>' maxlength="50" /><br/>
    <span id='register_username_errorloc' class='error'></span>
</div>
<div class='container'>
    <label for='password' >Password*:</label><br/>
    <input type='password' name='password' id='password' maxlength="50" />
    <div id='register_password_errorloc' class='error'></div>

    <label for='confirm_password' >Password Confirmation*:</label><br/>
    <input type='password' name='confirm_password' id='confirm_password' maxlength="50" />
    <div id='register_confirm_password_errorloc' class='error'></div>
</div>

<div class='container'>
    <input type='submit' name='Submit' value='Submit' />
</div>

</fieldset>
</form>
<!-- client-side Form Validations:
Uses the excellent form validation script from JavaScript-coder.com-->
<script type='text/javascript' src='scripts/validator.js'></script>
<script type='text/javascript'>
// <![CDATA[
    var formValidator = new Validator("register");
    formValidator.EnableOnPageErrorDisplay();
    formValidator.EnableMsgsTogether();
    formValidator.addValidation("first_name","req","Please provide your first name");
    formValidator.addValidation("last_name","req","Please provide your last name");
    formValidator.addValidation("email","req","Please provide your email address");
    formValidator.addValidation("email","email","Please provide a valid email address");
    formValidator.addValidation("username","req","Please provide a username");
    formValidator.addValidation("password","req","Please provide a password");
    formValidator.addValidation("confirm_password","req","Please provide a password confirmation");
    formValidator.addValidation("confirm_password","match","Password confirmation must match");
// ]]>
</script>

<!--
Form Code End (see html-form-guide.com for more info.)
-->

</body>
</html>