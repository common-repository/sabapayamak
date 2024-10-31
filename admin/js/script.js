document.addEventListener("DOMContentLoaded", function () {
    
    ks_disableLoadingAnimation();
    
    ks_toggleL2faSettings(true);
    
    const ks_event_select = document.getElementById('ks_event_select');
    if(ks_event_select)
        ks_hideUserNumberRadio(ks_event_select, true);

    const ks_patternInput = document.querySelector(".ks_pattern_input");
    if (ks_patternInput) {
        
        // اضافه کردن تگ‌ها به الگوی متن
        ks_patternTags.forEach(tag => {
            var ks_tagButtons = document.querySelectorAll("button.ks_pattern_" + tag);
            ks_tagButtons.forEach(button => {
                ks_addTagToPatternInput(ks_patternInput, button, "{" + tag + "}");
            });
        });

        // تغییر پیش‌نمایش متن الگو با تغییر متن
        const ks_PatternPreview = document.querySelector("textarea#ks_pattern_preview");
        ks_patternInput.onkeyup = function(){
            ks_PatternPreview.value = ks_replaceTagsWithValues(ks_patternInput.value);
            ks_patternInput.style.direction = ks_patternInput.value.match(/^[^a-z]*[^\x00-\x22\x24-\x7E]/ig) ? 'rtl' : 'ltr';
        }

        // پیش‌نمایش هنگام لود صفحه
        ks_patternInput.dispatchEvent(new Event('keyup'));
    }

    // ولیدیشن فرم اضافه کردن رویداد
    const addEventForm = document.querySelector(".ks_event_form");
    if (addEventForm != null) {
        addEventForm.addEventListener("submit", function (event) {
            if(!ks_eventFormValidation()){
                event.preventDefault();
                ks_disableLoadingAnimation();
            }
            else{
                ks_enableLoadingAnimation();
            }
        });
    };

    // ولیدیشن فرم ارسال پیامک
    const sendSmsForm = document.querySelector("#ks_send_sms_form");
    if (sendSmsForm != null) {
        sendSmsForm.addEventListener("submit", function (event) {
            if(!ks_sendFormValidation()){
                event.preventDefault();
                ks_disableLoadingAnimation();
            }
            else{
                ks_enableLoadingAnimation();
            }
        });
    };

    // در فیلدهای عددی تنها اجازه ورود عدد داده شود
    const numberInputs = document.querySelectorAll('.only-numbers-allowed');
    ks_onlyAllowNumbers(numberInputs);

    // نمایش فیلد بر اساس نوع ارسال در تب ارسال پیامک
    const sendSelectElement = document.getElementById("ks_send_type");
    if (sendSelectElement) {
        ks_ShowRelatedOptionBlock(sendSelectElement);
    }
    
    // نمایش فیلد بر اساس رویداد در تب افزودن/ویرایش رویداد
    const eventSelectElement = document.getElementById("ks_event_select");
    if (eventSelectElement) {
        ks_ShowRelatedOptionBlock(eventSelectElement);
    }

    // اضافه کردن شمارنده پیامک به رویداد ورودی متن پیامک
    const ks_smsInput = document.querySelector(".ks_sms_input");
    if (ks_smsInput) {
        ks_smsInput.addEventListener('keyup', () => {
            ks_checkSMSLength(".ks_textarea_count", "ks_sms_counter", 0);
            ks_hideSendFormInputValidation();
        });
    }

    const ks_submit = document.querySelector('div.ks_wrap input#submit')
        if(ks_submit){
            ks_submit.addEventListener('click', () => {
                ks_enableLoadingAnimation();
            });
        }
});

/**
 * نمایش لودینگ
 */
function ks_enableLoadingAnimation() {
    const loadingDiv = document.getElementById('ks_loading')
    if (loadingDiv) {
        loadingDiv.style.display = 'block';
    }
}

/**
 * عدم نمایش لودینگ
 */
function ks_disableLoadingAnimation() {
    const loadingDiv = document.getElementById('ks_loading')
    if (loadingDiv) {
        loadingDiv.style.display = 'none';
    }
}

/**
 * پیام تأیید حذف 
 */
function ks_confirmDelete() {
    if (confirm('آیا برای حذف مطمئن هستید؟')) {
        ks_enableLoadingAnimation();
        return true;
    } else {
        return false;
    }
}

/**
 * پیام تأیید حذف  بالک
 */
function ks_confirmDeleteBulk() {
    const bulkButton = document.querySelector('input#doaction')
    if(bulkButton){
        bulkButton.addEventListener('click', (e) =>{
            const bulkSelect = document.querySelector('#bulk-action-selector-top');
            if(bulkSelect.value == 'delete'){
                if (confirm('آیا برای حذف مطمئن هستید؟')) {
                    ks_enableLoadingAnimation();
                    return true;
                } else {
                    e.preventDefault();
                    return false;
                }
            }
            else{
                ks_enableLoadingAnimation();
            }
        });
    }
}

/**
 * نمایش/عدم نمایش تنظیمات ورود دومرحله‌ای
 */
function ks_toggleL2faSettings(onPageLoad = false){
    const checkbox = document.querySelector('#ks_l2fa_enabled');
    if (checkbox) {
        const l2faSettings = document.querySelector('#ks_l2fa_settings');
        const hidden = jQuery(ks_l2fa_settings).is(":hidden");
        if (checkbox.checked) {
            if (hidden) 
                jQuery(ks_l2fa_settings).slideToggle();
        }
        else { // !checkbox.checked
            if (!hidden) {
                if (onPageLoad) 
                    l2faSettings.style.display = 'none';
                else
                   jQuery(ks_l2fa_settings).slideToggle();
            }
        }
    }
}

/**
 * در فیلدهای پاس داده شده فقط اجازه ورود عدد داده شود
 */
function ks_onlyAllowNumbers(inputsArray){
    inputsArray.forEach(element => {
        element.onkeypress = function (e) {
            var charCode = (e.which) ? e.which : e.keyCode;
            if (element.value.length == 0) {
                if (charCode == 45) {
                    return true;
                }
            }
            else {
                if (charCode == 45) {
                    return false;
                }
            }
            return ks_isNumber(e);
        };
    });
}

/**
 * تشخیص عدد بودن کاراکتر ورودی
 */
function ks_isNumber(e) {
    var charCode = (e.which) ? e.which : e.keyCode;
    if (charCode > 31 && ((charCode < 48 || charCode > 57) && charCode != 45))
        return false;
}

/**
 * ولیدیشن برای فرم افزودن رویداد
 */
function ks_eventFormValidation(){
    let isValid = true;

    const eventSelect = document.querySelector("#ks_event_select");
    const eventSelectValidation = document.querySelector("#ks_event-select-validation");
    if (eventSelect.value === "") {
        isValid = false;
        eventSelectValidation.innerHTML = "رویداد مورد نظر را انتخاب کنید.";
    }
    else{
        eventSelectValidation.innerHTML = "";
    }

    const sendToNumber = document.querySelector("#ks_send_to_number");
    if (sendToNumber && sendToNumber.checked) {
        const eventMobileNumber = document.querySelector("#ks_mobile_number");
        const eventMobileNumberValidation = document.querySelector("#ks_event-mobile-validation");
        if (eventMobileNumber.value === "") {
            isValid = false;
            eventMobileNumberValidation.innerHTML = "شماره همراه را وارد کنید.";
        }
        else{
            eventMobileNumberValidation.innerHTML = "";
        }
    }

    const eventpattern = document.querySelector("#ks_pattern_input");
    const eventpatternValidation = document.querySelector("#ks_event-pattern-validation");
    if (eventpattern.value == "") {
        isValid = false;
        eventpatternValidation.innerHTML = "الگوی پیامک را وارد کنید.";
    }
    else{
        eventpatternValidation.innerHTML = "";
    }

    return isValid;
}

/**
 * ولیدیشن برای فرم ارسال پیامک
 */
function ks_sendFormValidation(){
    let isValid = true;

    const sendSelect = document.querySelector("#ks_send_type");
    const sendSelectValidation = document.querySelector("#ks_send_type_validation");
    if (sendSelect.value === "") {
        isValid = false;
        sendSelectValidation.innerHTML = "نوع ارسال را انتخاب کنید.";
    }
    else{
        sendSelectValidation.innerHTML = "";
    }

    const sendInput = document.querySelector("#ks_sms_input");
    const sendInputValidation = document.querySelector("#ks_sms_input_validation");
    if (sendInput.value == "") {
        isValid = false;
        sendInputValidation.innerHTML = "متن پیامک را وارد کنید.";
    }
    else{
        sendInputValidation.innerHTML = "";
    }

    return isValid;
}

/**
 * پنهان کردن خطاهای ولیدیشن متن پیام در فرم ارسال پیامک
 */
function ks_hideSendFormInputValidation(){
    ks_clearElementInnerHTMLById("ks_sms_input_validation")
}

/**
 * پنهان کردن خطاهای ولیدیشن انتخاب نوع در فرم ارسال پیامک
 */
function ks_hideSendFormSelectValidation(){
    ks_clearElementInnerHTMLById("ks_send_type_validation");
}

/**
 * پنهان کردن خطاهای ولیدیشن انتخاب نوع در فرم رویداد
 */
function ks_hideEventSelectValidation(){
    ks_clearElementInnerHTMLById("ks_event-select-validation");
}

/**
 * پنهان کردن خطاهای ولیدیشن متن الگو در فرم رویداد
 */
 function ks_hideEventPatternValidation(){
    ks_clearElementInnerHTMLById("ks_event-pattern-validation")
}

/**
 * پنهان کردن خطاهای ولیدیشن شماره در فرم رویداد
 */
 function ks_hideEventNumberValidation(){
    ks_clearElementInnerHTMLById("ks_event-mobile-validation")
}

/**
 * پاک کردن محتوای یک المنت
 * @param {string} id شناسه المنت
 */
function ks_clearElementInnerHTMLById(id){
    const element = document.querySelector("#" + id);
    if (element) element.innerHTML = "";
}

/**
 * اضافه کردن تگ  به ورودی الگوی پیامک
 */
function ks_addTagToPatternInput(inputElement, tagButtonElement, tag){
    if (tagButtonElement != null) {
        tagButtonElement.onclick = function () {
                inputElement.value = inputElement.value + tag;
                inputElement.dispatchEvent(new Event('keyup'));
        };
    }
}

/**
 * جایگزین کردن تگ‌ها با مقادیر برای پیش‌نمایش
 */
function ks_replaceTagsWithValues(input){
    input = input.ks_replaceAll("{OTP_CODE}", ks_getRandomCode());
    input = input.ks_replaceAll("{DATE}", ks_getDate());
    input = input.ks_replaceAll("{TIME}", ks_getTime());
    input = input.ks_replaceAll("{USER_ID}", "۸");
    input = input.ks_replaceAll("{USER_NAME}", "user");
    input = input.ks_replaceAll("{COMMENT_AUTHOR}", "حسین");
    input = input.ks_replaceAll("{COMMENT_CONTENT}", "مطلب مفیدی بود");
    input = input.ks_replaceAll("{COMMENT_POST_TITLE}", "سلام دنیا!");
    input = input.ks_replaceAll("{POST_ID}", "۸۸");
    input = input.ks_replaceAll("{POST_TITLE}", "سلام دنیا!");
    input = input.ks_replaceAll("{PLUGIN_NAME}", "صباپیامک");
    return input;
}

/**
 * جایگزین کردن همه مقادیر در متن
 * @param {string} search 
 * @param {string} replacement 
 * @returns string
 */
String.prototype.ks_replaceAll = function(search, replacement) {
    var target = this;
    return target.split(search).join(replacement);
};

/**
 * گرفتن تاریخ شمسی
 */
function ks_getDate(){
    const dateFormat = new Intl.DateTimeFormat("fa");
    date = dateFormat.format(Date.now());
    return date;
}

/**
 * گرفتن ساعت
 */
function ks_getTime(delimiter = ':'){
    const date = new Date();
    const hour = String(ks_replaceDigitsEn2Fa(date.getHours())).padStart(2, '۰');
    const minute = String(ks_replaceDigitsEn2Fa(date.getMinutes())).padStart(2, '۰');
    const second = String(ks_replaceDigitsEn2Fa(date.getSeconds())).padStart(2, '۰');
    const time = hour + delimiter + minute + delimiter + second;
    return time; 
}

/**
 * گرفتن کد تصادفی
 */
function ks_getRandomCode(){
    window.randomL2faCode;
    if(window.randomL2faCode === undefined){
        window.randomL2faCode = Math.floor(Math.random() * 1000000); 
        while (window.randomL2faCode.toString().length < 6) {
            window.randomL2faCode += '0';
        }
    }
    
    const random = ks_replaceDigitsEn2Fa(window.randomL2faCode);

    return random; 
}

/**
 * تبدیل اعداد انگلیسی به فارسی
 */
function ks_replaceDigitsEn2Fa(input){
    const numberFormat = new Intl.NumberFormat("fa",{useGrouping:false});
    const output = numberFormat.format(input);
    return output; 
}

/**
 * اضافه کردن پیام به بخش اعلانات پنل ادمین
 */
function ks_addNotice(message, type){
    setTimeout(function () {
        const placeholder = document.getElementById("ks_notices_placeholder");
        if (placeholder != null) {
            placeholder.innerHTML += "<div class='notice notice-" + type + " is-dismissible'> <p><strong>" + message + "</strong></p> </div>";
            placeholder.firstChild.innerHTML += '<button onclick="jQuery(this.parentNode.parentNode).slideToggle(\'fast\');" type="button" class="notice-dismiss"><span class="screen-reader-text">رد کردن این اخطار</span></button>';
        }
    }, 1);
}

/**
 * نمایش بلوک آپشن مربوط به سلکت
 */
function ks_ShowRelatedOptionBlock(selectElement){

    Array.from(selectElement.options).forEach(option => {
        if (option.value != "") {
            value = document.getElementById(option.value);
            if (value)
                value.style.display = "none";
        }
    });

    if (selectElement.value) {
        selected = document.getElementById(selectElement.value);
        if (selected) 
            selected.style.display = "block";
    }
}

/**
 * پنهان کردن رادیوی انتخاب موبایل کاربر برای رویدادهایی که پشتیبانی نمی‌کنند.
 */
function ks_hideUserNumberRadio(selectElement, onPageLoad = false) {
    if (ks_can_user_array.includes(selectElement.value)){
        document.getElementById('ks_can_send_to_user').style.display = "block";
    }
    else{
        document.getElementById('ks_can_send_to_user').style.display = "none";
        document.getElementById('ks_send_to_number').checked = true; // انتخاب رادیوی شماره
    }

    if (!onPageLoad) {
        document.getElementById('ks_pattern_input').value = ""; // پاک کردن متن الگو
        document.getElementById('ks_pattern_input').dispatchEvent(new Event('keyup')); // پاک کردن پیش‌نمایش
    }
}

/**
 * چک کردن طول پیامک
 */
function ks_checkSMSLength(textarea, counterSpan, def) {
    const link = 0;
    if (document.querySelector(textarea) == null)
        return;

    var text = document.querySelector(textarea).value;
    var ucs2 = text.search(/[^\x00-\x7E]/) != -1
    if (!ucs2) 
      text = text.replace(/([[\]{}~^|\\])/g, "\\$1");
    var unitLength = ucs2 ? 70 : 160;
    var msgLen = 0;
    msgLen = document.querySelector(textarea).value.length + def;

    document.querySelector(textarea).style.direction = text.match(/^[^a-z]*[^\x00-\x7E]/ig) ? 'rtl' : 'ltr';

    var fa_diff = 3;
    var en_diff = 7;
    if (link == 2) {
        fa_diff = 4;
        en_diff = 10;
    }

    if (msgLen > unitLength) {
        if (ucs2) unitLength = unitLength - fa_diff;
        else unitLength = unitLength - en_diff;
    }

    var count = Math.max(Math.ceil(msgLen / unitLength), 1);
    
    document.getElementById(counterSpan).innerHTML = ks_replaceDigitsEn2Fa((unitLength * count - msgLen)) + ' (' + ks_replaceDigitsEn2Fa(count) + ')';

}

