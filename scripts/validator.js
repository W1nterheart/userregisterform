function Validator(frmname) {
    this.formobj = document.forms[frmname];
    if (!this.formobj) {
        alert("Error: couldn't get Form object " + frmname);
        return;
    }
    if (this.formobj.onsubmit) {
        this.formobj.old_onsubmit = this.formobj.onsubmit;
        this.formobj.onsubmit = null;
    } else {
        this.formobj.old_onsubmit = null;
    }
    this.formobj._sfm_form_name = frmname;
    this.formobj.onsubmit = form_submit_handler;
    this.addValidation = add_validation;
    document.error_disp_handler = new sfm_ErrorDisplayHandler();
    this.EnableOnPageErrorDisplay = validator_enable_OPED;
    this.show_errors_together = true;
    this.EnableMsgsTogether = sfm_enable_show_msgs_together;
}
function set_addnl_vfunction(functionname) {
    this.formobj.addnlvalidation = functionname;
}
function sfm_enable_show_msgs_together() {
    this.show_errors_together = true;
    this.formobj.show_errors_together = true;
}
function clear_all_validations() {
    for (var itr = 0; itr < this.formobj.elements.length; itr++) {
        this.formobj.elements[itr].validationset = null;
    }
}
function form_submit_handler() {
    var bRet = true;
    document.error_disp_handler.clear_msgs();
    for (var itr = 0; itr < this.elements.length; itr++) {
        if (this.elements[itr].validationset && !this.elements[itr].validationset.validate()) {
            bRet = false;
        }
        if (!bRet && !this.show_errors_together) {
            break;
        }
    }
    if (!bRet) {
        document.error_disp_handler.FinalShowMsg();
        return false;
    }

    if (this.addnlvalidation) {
        str = " var ret = " + this.addnlvalidation + "()";
        eval(str);
        if (!ret) return ret;
    }
    return true;
}
function add_validation(itemname, descriptor, errstr) {
    var condition = null;
    if (arguments.length > 3) {
        condition = arguments[3];
    }
    if (!this.formobj) {
        alert("Error: The form object is not set properly");
        return;
    }//if
    var itemobj = this.formobj[itemname];
    if (itemobj.length && isNaN(itemobj.selectedIndex))
    //for radio button; don't do for 'select' item
    {
        itemobj = itemobj[0];
    }
    if (!itemobj) {
        alert("Error: Couldnot get the input object named: " + itemname);
        return;
    }
    if (!itemobj.validationset) {
        itemobj.validationset = new ValidationSet(itemobj, this.show_errors_together);
    }
    itemobj.validationset.add(descriptor, errstr, condition);
    itemobj.validatorobj = this;
}
function validator_enable_OPED() {
    document.error_disp_handler.EnableOnPageDisplay(false);
}

function validator_enable_OPED_SB() {
    document.error_disp_handler.EnableOnPageDisplay(true);
}
function sfm_ErrorDisplayHandler() {
    this.msgdisplay = new AlertMsgDisplayer();
    this.EnableOnPageDisplay = edh_EnableOnPageDisplay;
    this.ShowMsg = edh_ShowMsg;
    this.FinalShowMsg = edh_FinalShowMsg;
    this.all_msgs = new Array();
    this.clear_msgs = edh_clear_msgs;
}
function edh_clear_msgs() {
    this.msgdisplay.clearmsg(this.all_msgs);
    this.all_msgs = new Array();
}
function edh_FinalShowMsg() {
    this.msgdisplay.showmsg(this.all_msgs);
}
function edh_EnableOnPageDisplay(single_box) {
    if (true == single_box) {
        this.msgdisplay = new SingleBoxErrorDisplay();
    }
    else {
        this.msgdisplay = new DivMsgDisplayer();
    }
}
function edh_ShowMsg(msg, input_element) {

    var objmsg = new Array();
    objmsg["input_element"] = input_element;
    objmsg["msg"] = msg;
    this.all_msgs.push(objmsg);
}
function AlertMsgDisplayer() {
    this.showmsg = alert_showmsg;
    this.clearmsg = alert_clearmsg;
}
function alert_clearmsg(msgs) {

}
function alert_showmsg(msgs) {
    var whole_msg = "";
    var first_elmnt = null;
    for (var m in msgs) {
        if (null == first_elmnt) {
            first_elmnt = msgs[m]["input_element"];
        }
        whole_msg += msgs[m]["msg"] + "\n";
    }

    alert(whole_msg);

    if (null != first_elmnt) {
        first_elmnt.focus();
    }
}
function sfm_show_error_msg(msg, input_elmt) {
    document.error_disp_handler.ShowMsg(msg, input_elmt);
}
function SingleBoxErrorDisplay() {
    this.showmsg = sb_div_showmsg;
    this.clearmsg = sb_div_clearmsg;
}

function sb_div_clearmsg(msgs) {
    var divname = form_error_div_name(msgs);
    show_div_msg(divname, "");
}

function sb_div_showmsg(msgs) {
    var whole_msg = "<ul>\n";
    for (var m in msgs) {
        whole_msg += "<li>" + msgs[m]["msg"] + "</li>\n";
    }
    whole_msg += "</ul>";
    var divname = form_error_div_name(msgs);
    show_div_msg(divname, whole_msg);
}
function form_error_div_name(msgs) {
    var input_element = null;

    for (var m in msgs) {
        input_element = msgs[m]["input_element"];
        if (input_element) {
            break;
        }
    }

    var divname = "";
    if (input_element) {
        divname = input_element.form._sfm_form_name + "_errorloc";
    }

    return divname;
}
function DivMsgDisplayer() {
    this.showmsg = div_showmsg;
    this.clearmsg = div_clearmsg;
}
function div_clearmsg(msgs) {
    for (var m in msgs) {
        var divname = element_div_name(msgs[m]["input_element"]);
        show_div_msg(divname, "");
    }
}
function element_div_name(input_element) {
    var divname = input_element.form._sfm_form_name + "_" +
        input_element.name + "_errorloc";

    divname = divname.replace(/[\[\]]/gi, "");

    return divname;
}
function div_showmsg(msgs) {
    var whole_msg;
    var first_elmnt = null;
    for (var m in msgs) {
        if (null == first_elmnt) {
            first_elmnt = msgs[m]["input_element"];
        }
        var divname = element_div_name(msgs[m]["input_element"]);
        show_div_msg(divname, msgs[m]["msg"]);
    }
    if (null != first_elmnt) {
        first_elmnt.focus();
    }
}
function show_div_msg(divname, msgstring) {
    if (divname.length <= 0) return false;

    if (document.layers) {
        divlayer = document.layers[divname];
        if (!divlayer) {
            return;
        }
        divlayer.document.open();
        divlayer.document.write(msgstring);
        divlayer.document.close();
    }
    else if (document.all) {
        divlayer = document.all[divname];
        if (!divlayer) {
            return;
        }
        divlayer.innerHTML = msgstring;
    }
    else if (document.getElementById) {
        divlayer = document.getElementById(divname);
        if (!divlayer) {
            return;
        }
        divlayer.innerHTML = msgstring;
    }
    divlayer.style.visibility = "visible";
    return false;
}
function ValidationDesc(inputitem, desc, error, condition) {
    this.desc = desc;
    this.error = error;
    this.itemobj = inputitem;
    this.condition = condition;
    this.validate = vdesc_validate;
}
function vdesc_validate() {
    if (this.condition != null) {
        if (!eval(this.condition)) {
            return true;
        }
    }
    if (!validateInput(this.desc, this.itemobj, this.error)) {
        this.itemobj.validatorobj.disable_validations = true;
        this.itemobj.focus();
        return false;
    }
    return true;
}
function ValidationSet(inputitem, msgs_together) {
    this.vSet = new Array();
    this.add = add_validationdesc;
    this.validate = vset_validate;
    this.itemobj = inputitem;
    this.msgs_together = msgs_together;
}
function add_validationdesc(desc, error, condition) {
    this.vSet[this.vSet.length] =
        new ValidationDesc(this.itemobj, desc, error, condition);
}
function vset_validate() {
    var bRet = true;
    for (var itr = 0; itr < this.vSet.length; itr++) {
        bRet = bRet && this.vSet[itr].validate();
        if (!bRet && !this.msgs_together) {
            break;
        }
    }
    return bRet;
}
function validateEmail(email) {
    var splitted = email.match("^(.+)@(.+)$");
    if (splitted == null) return false;
    if (splitted[1] != null) {
        var regexp_user = /^\"?[\w-_\.]*\"?$/;
        if (splitted[1].match(regexp_user) == null) return false;
    }
    if (splitted[2] != null) {
        var regexp_domain = /^[\w-\.]*\.[A-Za-z]{2,4}$/;
        if (splitted[2].match(regexp_domain) == null) {
            var regexp_ip = /^\[\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\]$/;
            if (splitted[2].match(regexp_ip) == null) return false;
        }// if
        return true;
    }
    return false;
}

function TestRequiredInput(objValue, strError) {
    var ret = true;
    if (eval(objValue.value.length) == 0) {
        if (!strError || strError.length == 0) {
            strError = objValue.name + " : Required Field";
        }//if
        sfm_show_error_msg(strError, objValue);
        ret = false;
    }//if 
    return ret;
}
function TestMaxLen(objValue, strMaxLen, strError) {
    var ret = true;
    if (eval(objValue.value.length) > eval(strMaxLen)) {
        if (!strError || strError.length == 0) {
            strError = objValue.name + " : " + strMaxLen + " characters maximum ";
        }//if
        sfm_show_error_msg(strError, objValue);
        ret = false;
    }//if 
    return ret;
}

function TestEmail(objValue, strError) {
    var ret = true;
    if (objValue.value.length > 0 && !validateEmail(objValue.value)) {
        if (!strError || strError.length == 0) {
            strError = objValue.name + ": Enter a valid Email address ";
        }//if
        sfm_show_error_msg(strError, objValue);
        ret = false;
    }//if
    return ret;
}

function validateInput(strValidateStr, objValue, strError) {
    var ret = true;
    var epos = strValidateStr.search("=");
    var command = "";
    var cmdvalue = "";
    if (epos >= 0) {
        command = strValidateStr.substring(0, epos);
        cmdvalue = strValidateStr.substr(epos + 1);
    }
    else {
        command = strValidateStr;
    }
    switch (command) {
        case "req":
        case "required":
        {
            ret = TestRequiredInput(objValue, strError)
            break;
        }//case required
        case "maxlength":
        case "maxlen":
        {
            ret = TestMaxLen(objValue, cmdvalue, strError)
            break;
        }//case maxlen
        case "email":
        {
            ret = TestEmail(objValue, strError);
            break;
        }
    }//switch 
    return ret;
}