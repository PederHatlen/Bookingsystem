//////////////////////////////////////
/// Originalt hentet fra Binærchat ///
//////////////////////////////////////

// Declarring/finding ellements used later
let usernameEL = document.querySelector("#username");

let pwdEL = document.querySelector("#password");
let pwdConEL = document.querySelector("#passwordControll");

let submitbtn = document.querySelector("#submit");

// Checking the input on the second password field if it matches the first, then unlock submit button
function checkinput() {
    if (usernameEL.value != "" && pwdEL.value != "" && pwdEL.value === pwdConEL.value) {
        submitbtn.style.backgroundColor = "";
        submitbtn.disabled = false;
    }else{
        submitbtn.style.backgroundColor = "Gray";
        submitbtn.disabled = true;
    }
}

// Apply checkinput function when there is anny change in the input fields values
usernameEL.oninput = checkinput;
pwdEL.oninput = checkinput;
pwdConEL.oninput = checkinput;
checkinput()