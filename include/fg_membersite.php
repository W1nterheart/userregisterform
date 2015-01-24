<?PHP

require_once("formvalidator.php");

class FGMembersite
{
    private $db_host;
    private $username;
    private $pwd;
    private $database;
    private $tableName;
    private $connection;
    private $randKey;

    private $error_message;

    //-----Initialization -------
    function FGMembersite()
    {
        $this->sitename = 'YourWebsiteName.com';
        $this->randKey = '0iQx5oBk66oVZep';
    }

    function InitDB($host, $uname, $pwd, $database, $tableName)
    {
        $this->db_host = $host;
        $this->username = $uname;
        $this->pwd = $pwd;
        $this->database = $database;
        $this->tableName = $tableName;

    }
    
    function SetWebsiteName($sitename)
    {
        $this->sitename = $sitename;
    }

    function SetRandomKey($key)
    {
        $this->randKey = $key;
    }

    //-------Main Operations ----------------------
    function RegisterUser()
    {
        if (!isset($_POST['submitted'])) {
            return false;
        }

        $formVars = array();

        if (!$this->ValidateRegistrationSubmission()) {
            return false;
        }

        $this->CollectRegistrationSubmission($formVars);

        if (!$this->SaveToDatabase($formVars)) {
            return false;
        }

        return true;
    }

    function Login()
    {
        if (empty($_POST['username'])) {
            $this->HandleError("UserName is empty!");
            return false;
        }

        if (empty($_POST['password'])) {
            $this->HandleError("Password is empty!");
            return false;
        }

        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        if (!isset($_SESSION)) {
            session_start();
        }
        if (!$this->CheckLoginInDB($username, $password)) {
            return false;
        }

        $_SESSION[$this->GetLoginSessionVar()] = $username;

        return true;
    }

    function CheckLogin()
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        $sessionvar = $this->GetLoginSessionVar();

        if (empty($_SESSION[$sessionvar])) {
            return false;
        }
        return true;
    }

    function UserFullName()
    {
        return isset($_SESSION['name_of_user']) ? $_SESSION['name_of_user'] : '';
    }

    function UserEmail()
    {
        return isset($_SESSION['email_of_user']) ? $_SESSION['email_of_user'] : '';
    }

    function LogOut()
    {
        session_start();

        $sessionvar = $this->GetLoginSessionVar();

        $_SESSION[$sessionvar] = NULL;

        unset($_SESSION[$sessionvar]);
    }

    function ChangePassword()
    {
        if (!$this->CheckLogin()) {
            $this->HandleError("Not logged in!");
            return false;
        }

        if (empty($_POST['oldpwd'])) {
            $this->HandleError("Old password is empty!");
            return false;
        }
        if (empty($_POST['newpwd'])) {
            $this->HandleError("New password is empty!");
            return false;
        }

        $user_rec = array();
        if (!$this->GetUserFromEmail($this->UserEmail(), $user_rec)) {
            return false;
        }

        $pwd = trim($_POST['oldpwd']);

        $salt = $user_rec['salt'];
        $hash = $this->checkHashSSHA($salt, $pwd);

        if ($user_rec['password'] != $hash) {
            $this->HandleError("The old password does not match!");
            return false;
        }
        $newpwd = trim($_POST['newpwd']);

        if (!$this->ChangePasswordInDB($user_rec, $newpwd)) {
            return false;
        }
        return true;
    }

    //-------Public Helper functions -------------
    function GetSelfScript()
    {
        return htmlentities($_SERVER['PHP_SELF']);
    }

    function SafeDisplay($value_name)
    {
        if (empty($_POST[$value_name])) {
            return '';
        }
        return htmlentities($_POST[$value_name]);
    }

    function RedirectToURL($url)
    {
        header("Location: $url");
        exit;
    }

    function GetSpamTrapInputName()
    {
        return 'sp' . md5('KHGdnbvsgst' . $this->randKey);
    }

    function GetErrorMessage()
    {
        if (empty($this->error_message)) {
            return '';
        }
        $errormsg = nl2br(htmlentities($this->error_message));
        return $errormsg;
    }

    //-------Private Helper functions-----------

    function HandleError($err)
    {
        $this->error_message .= $err . "\r\n";
    }

    function HandleDBError($err)
    {
        $this->HandleError($err . "\r\n mysqlerror:" . mysql_error());
    }

    function GetLoginSessionVar()
    {
        $retvar = md5($this->randKey);
        $retvar = 'usr_' . substr($retvar, 0, 10);
        return $retvar;
    }

    function CheckLoginInDB($username, $password)
    {
        if (!$this->DBLogin()) {
            $this->HandleError("Database login failed!");
            return false;
        }
        $username = $this->SanitizeForSQL($username);

        $nresult = $this->connection->query("SELECT * FROM $this->tableName WHERE username = '$username'", $this->connection) or die(mysql_error());
        // check for result 
        $no_of_rows = mysql_num_rows($nresult);
        if ($no_of_rows > 0) {
            $nresult = mysql_fetch_array($nresult);
            $salt = $nresult['salt'];
            $encrypted_password = $nresult['password'];
            $hash = $this->checkHashSSHA($salt, $password);


        }


        $qry = "SELECT name, email FROM $this->tableName WHERE username='$username' and password='$hash' and confirmcode='y'";

        $result = $this->connection->query($qry);

        if (!$result || mysql_num_rows($result) <= 0) {
            $this->HandleError("Error logging in. The username or password does not match");
            return false;
        }

        $row = mysql_fetch_assoc($result);


        $_SESSION['name_of_user'] = $row['name'];
        $_SESSION['email_of_user'] = $row['email'];

        return true;
    }

    public function checkHashSSHA($salt, $password)
    {

        $hash = base64_encode(sha1($password . $salt, true) . $salt);

        return $hash;
    }

    function UpdateDBRecForConfirmation(&$user_rec)
    {
        if (!$this->DBLogin()) {
            $this->HandleError("Database login failed!");
            return false;
        }
        $confirmcode = $this->SanitizeForSQL($_GET['code']);

        $result = $this->connection->query("SELECT name, email FROM $this->tableName WHERE confirmcode='$confirmcode'", $this->connection);
        if (!$result || mysql_num_rows($result) <= 0) {
            $this->HandleError("Wrong confirm code.");
            return false;
        }
        $row = mysql_fetch_assoc($result);
        $user_rec['name'] = $row['name'];
        $user_rec['email'] = $row['email'];

        $qry = "UPDATE $this->tableName SET confirmcode='y' WHERE confirmcode='$confirmcode'";

        if (!$this->connection->query($qry)) {
            $this->HandleDBError("Error inserting data to the table\nquery:$qry");
            return false;
        }
        return true;
    }

    function ResetUserPasswordInDB($user_rec)
    {
        $new_password = substr(md5(uniqid()), 0, 10);

        if (false == $this->ChangePasswordInDB($user_rec, $new_password)) {
            return false;
        }
        return $new_password;
    }

    function ChangePasswordInDB($user_rec, $newpwd)
    {
        $newpwd = $this->SanitizeForSQL($newpwd);

        $hash = $this->hashSSHA($newpwd);

        $new_password = $hash["encrypted"];

        $salt = $hash["salt"];

        $qry = "UPDATE $this->tableName SET password='" . $new_password . "', salt='" . $salt . "' WHERE id_user=" . $user_rec['id_user'] . "";

        if (!$this->connection->query($qry)) {
            $this->HandleDBError("Error updating the password \nquery:$qry");
            return false;
        }
        return true;
    }

    function GetUserFromEmail($email, &$user_rec)
    {
        if (!$this->DBLogin()) {
            $this->HandleError("Database login failed!");
            return false;
        }
        $email = $this->SanitizeForSQL($email);

        $result = $this->connection->query("SELECT * FROM $this->tableName WHERE email='$email'", $this->connection);

        if (!$result || mysql_num_rows($result) <= 0) {
            $this->HandleError("There is no user with email: $email");
            return false;
        }
        $user_rec = mysql_fetch_assoc($result);


        return true;
    }
    
    function GetResetPasswordCode($email)
    {
        return substr(md5($email . $this->sitename . $this->randKey), 0, 10);
    }

    function ValidateRegistrationSubmission()
    {
        //This is a hidden input field. Humans won't fill this field.
        if (!empty($_POST[$this->GetSpamTrapInputName()])) {
            //The proper error is not given intentionally
            $this->HandleError("Automated submission prevention: case 2 failed");
            return false;
        }

        $validator = new FormValidator();
        $validator->addValidation("first_name", "req", "Please fill in first name");
        $validator->addValidation("last_name", "req", "Please fill in last name");
        $validator->addValidation("email", "email", "The input for Email should be a valid email value");
        $validator->addValidation("email", "req", "Please fill in Email");

        $validator->addValidation("password", "req", "Please fill in Password");

        $valid = $validator->ValidateForm();
        if (!$valid) {
            $error = '';
            $error_hash = $validator->GetErrors();
            foreach ($error_hash as $inpname => $inp_err) {
                $error .= $inpname . ':' . $inp_err . "\n";
            }
            $this->HandleError($error);
            return false;
        }
        return true;
    }

    function CollectRegistrationSubmission(&$formVars)
    {
        $formVars['first_name'] = $this->Sanitize($_POST['first_name']);
        $formVars['last_name'] = $this->Sanitize($_POST['last_name']);
        $formVars['username'] = $this->Sanitize($_POST['username']);
        $formVars['email'] = $this->Sanitize($_POST['email']);
        $formVars['password'] = $this->Sanitize($_POST['password']);

    }

    function SaveToDatabase(&$formVars)
    {
        if (!$this->DBLogin()) {
            $this->HandleError("Database login failed!");
            return false;
        }
        if (!$this->EnsureTable()) {
            return false;
        }
        if ($this->IsFieldUnique($formVars, 'email')) {
            $this->HandleError("This email is already registered");
            return false;
        }

        if ($this->IsFieldUnique($formVars, 'username')) {
            $this->HandleError("This UserName is already used. Please try another username");
            return false;
        }

        if (!$this->InsertIntoDB($formVars)) {
            $this->HandleError("Inserting to Database failed!");
            return false;
        }
        return true;
    }

    function IsFieldUnique($formVars, $fieldName)
    {
        $field_val = $this->SanitizeForSQL($formVars[$fieldName]);
        $result = $this->connection
            ->query("SELECT username FROM $this->tableName WHERE $fieldName='" . $field_val . "'")
            ->fetchAll();
        return count($result);
    }

    function DBLogin()
    {
        $this->connection = new PDO("mysql:host=$this->db_host;dbname=$this->database", $this->username, $this->pwd);;

        if (!$this->connection) {
            $this->HandleDBError("Database Login failed! Please make sure that the DB login credentials provided are correct");
            return false;
        }

        return true;
    }

    function EnsureTable()
    {
        $result = $this->connection->query("SHOW COLUMNS FROM $this->tableName");
        if (!$result || count($result) <= 0) {
            return $this->CreateTable();
        }
        return true;
    }

    function CreateTable()
    {

        $qry = "CREATE TABLE $this->tableName (" .
            "id_user INT NOT NULL AUTO_INCREMENT ," .
            "first_name VARCHAR( 128 ) NOT NULL ," .
            "last_name VARCHAR( 128 ) NOT NULL ," .
            "email VARCHAR( 64 ) NOT NULL UNIQUE," .
            "phone_number VARCHAR( 16 ) NOT NULL ," .
            "username VARCHAR( 16 ) NOT NULL ," .
            "salt VARCHAR( 50 ) NOT NULL ," .
            "password VARCHAR( 80 ) NOT NULL ," .
            "confirmcode VARCHAR(32) ," .
            "PRIMARY KEY ( id_user ) " .
            ")";


        if (!$this->connection->query($qry)) {
            $this->HandleDBError("Error creating the table \nquery was\n $qry");
            return false;
        }
        return true;
    }

    function InsertIntoDB(&$formVars)
    {

        $confirmcode = $this->MakeConfirmationMd5($formVars['email']);

        $formVars['confirmcode'] = $confirmcode;

        $hash = $this->hashSSHA($formVars['password']);

        $encrypted_password = $hash["encrypted"];


        $salt = $hash["salt"];

        $insert_query = 'insert into ' . $this->tableName . '(
		first_name,
		last_name,
		email,
		username,	
		password,
		salt,
		confirmcode
		)
		values
		(
		"' . $this->SanitizeForSQL($formVars['first_name']) . '",
		"' . $this->SanitizeForSQL($formVars['last_name']) . '",
		"' . $this->SanitizeForSQL($formVars['email']) . '",
		"' . $this->SanitizeForSQL($formVars['username']) . '",
		"' . $encrypted_password . '",
		"' . $salt . '",
		"' . $confirmcode . '"
		)';


        if (!$this->connection->query($insert_query)) {
            $this->HandleDBError("Error inserting data to the table\nquery:$insert_query");
            return false;
        }
        return true;
    }

    function hashSSHA($password)
    {

        $salt = sha1(rand());
        $salt = substr($salt, 0, 10);
        $encrypted = base64_encode(sha1($password . $salt, true) . $salt);
        $hash = array("salt" => $salt, "encrypted" => $encrypted);
        return $hash;
    }

    function MakeConfirmationMd5($email)
    {
        $randno1 = rand();
        $randno2 = rand();
        return md5($email . $this->randKey . $randno1 . '' . $randno2);
    }

    function SanitizeForSQL($str)
    {
        return addslashes($str);
    }

    /*
       Sanitize() function removes any potential threat FROM the
       data submitted. Prevents email injections or any other hacker attempts.
       if $remove_nl is true, newline chracters are removed FROM the input.
       */
    function Sanitize($str, $remove_nl = true)
    {
        $str = $this->StripSlashes($str);

        if ($remove_nl) {
            $injections = array('/(\n+)/i',
                '/(\r+)/i',
                '/(\t+)/i',
                '/(%0A+)/i',
                '/(%0D+)/i',
                '/(%08+)/i',
                '/(%09+)/i'
            );
            $str = preg_replace($injections, '', $str);
        }

        return $str;
    }

    function StripSlashes($str)
    {
        if (get_magic_quotes_gpc()) {
            $str = stripslashes($str);
        }
        return $str;
    }
}
