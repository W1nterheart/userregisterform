<?PHP

/**
 * Carries information about each of the form validations
 */
class ValidatorObj
{
    var $variableName;
    var $validatorString;
    var $errorString;
}

/**
 * Base class for custom validation objects
 **/
class CustomValidator
{
    function DoValidate(&$formars, &$errorHash)
    {
        return true;
    }
}

/** Default error messages*/
define("E_VAL_REQUIRED_VALUE", "Please enter the value for %s");
define("E_VAL_EMAIL_CHECK_FAILED", "Please provide a valid email address");

/**
 * FormValidator: The main class that does all the form validations
 **/
class FormValidator
{
    var $validatorArray;
    var $errorHash;
    var $customValidators;

    function FormValidator()
    {
        $this->validatorArray = array();
        $this->errorHash = array();
        $this->customValidators = array();
    }

    function AddCustomValidator(&$customv)
    {
        array_push($this->customValidators, $customv);
    }

    function addValidation($variable, $validator, $error)
    {
        $validator_obj = new ValidatorObj();
        $validator_obj->variableName = $variable;
        $validator_obj->validatorString = $validator;
        $validator_obj->errorString = $error;
        array_push($this->validatorArray, $validator_obj);
    }

    function GetErrors()
    {
        return $this->errorHash;
    }

    function ValidateForm()
    {
        $bret = true;

        $errorString = "";
        $errorToDisplay = "";


        if (strcmp($_SERVER['REQUEST_METHOD'], 'POST') == 0) {
            $formVariables = $_POST;
        } else {
            $formVariables = $_GET;
        }

        $vcount = count($this->validatorArray);

        foreach ($this->validatorArray as $valObj) {
            if (!$this->ValidateObject($valObj, $formVariables, $errorString)) {
                $bret = false;
                $this->errorHash[$valObj->variableName] = $errorString;
            }
        }

        if (true == $bret) {
            foreach ($this->customValidators as $customVal) {
                if (false == $customVal->DoValidate($formVariables, $this->errorHash)) {
                    $bret = false;
                }
            }
        }
        return $bret;
    }

    function ValidateObject($validatorObj, $formVariables, &$errorString)
    {
        $bret = true;

        $splitted = explode("=", $validatorObj->validatorString);
        $command = $splitted[0];
        $commandValue = '';

        if (isset($splitted[1]) && strlen($splitted[1]) > 0) {
            $commandValue = $splitted[1];
        }

        $defaultErrorMessage = "";

        $inputValue = "";

        if (isset($formVariables[$validatorObj->variableName])) {
            $inputValue = $formVariables[$validatorObj->variableName];
        }

        $bret = $this->ValidateCommand($command, $commandValue, $inputValue,
            $defaultErrorMessage,
            $validatorObj->variableName,
            $formVariables);


        if (false == $bret) {
            if (isset($validatorObj->errorString) &&
                strlen($validatorObj->errorString) > 0
            ) {
                $errorString = $validatorObj->errorString;
            } else {
                $errorString = $defaultErrorMessage;
            }

        }//if
        return $bret;
    }

    function validate_req($inputValue, &$defaultErrorMessage, $variableName)
    {
        $bret = true;
        if (!isset($inputValue) ||
            strlen($inputValue) <= 0
        ) {
            $bret = false;
            $defaultErrorMessage = sprintf(E_VAL_REQUIRED_VALUE, $variableName);
        }
        return $bret;
    }

    function validate_email($email)
    {
        return preg_match("/^[_\\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\\.)+[a-zA-Z]{2,6}$/i", $email);
    }

    function ValidateCommand($command, $commandValue, $inputValue, &$defaultErrorMessage, $variableName, $formVariables)
    {
        $bret = true;
        switch ($command) {
            case 'req': {
                $bret = $this->validate_req($inputValue, $defaultErrorMessage, $variableName);
                break;
            }
            case 'email': {
                if (isset($inputValue) && strlen($inputValue) > 0) {
                    $bret = $this->validate_email($inputValue);
                    if (false == $bret) {
                        $defaultErrorMessage = E_VAL_EMAIL_CHECK_FAILED;
                    }
                }
                break;
            }
            case "regexp": {
                if (isset($inputValue) && strlen($inputValue) > 0) {
                    if (!preg_match("$commandValue", $inputValue)) {
                        $bret = false;
                        $defaultErrorMessage = sprintf(E_VAL_REGEXP_CHECK_FAILED, $variableName);
                    }
                }
                break;
            }
        }//switch
        return $bret;
    }//validate command
}