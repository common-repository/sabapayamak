document.addEventListener("DOMContentLoaded", function () {
    // در فیلدهای عددی تنها اجازه ورود عدد داده شود
    const numberInputs = document.querySelectorAll('.only-numbers-allowed');
    ks_onlyAllowNumbers(numberInputs);
});

// در فیلدهای پاس داده شده فقط اجازه ورود عدد داده شود
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

// تشخیص عدد بودن کاراکتر ورودی 
function ks_isNumber(e) {
    var charCode = (e.which) ? e.which : e.keyCode;
    if (charCode > 31 && ((charCode < 48 || charCode > 57) && charCode != 45))
        return false;
}
