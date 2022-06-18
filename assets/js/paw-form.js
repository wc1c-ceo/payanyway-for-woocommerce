/**
 * PAW js form
 */

document.addEventListener("DOMContentLoaded", function () {

    document.getElementById("place_order").style.display = "none";

    var paywrapper = document.getElementById("payment");
    var payul = paywrapper.getElementsByTagName("ul");
    if (payul[0] != undefined) {
        payul[0].style.paddingLeft = "0";
    }

    var pawwrapper = document.getElementById("pawformwrapper");
    var inputs = pawwrapper.innerHTML;

    inputs = '<form id="pawreplform" action="https://www.payanyway.ru/assistant.htm" method="GET">'
            + inputs
            + '<input type="submit" value="Оплатить">'
            + '</form>';

    var replacement = document.createElement('pawreplform');
    replacement.innerHTML = inputs;

    pawwrapper.replaceWith(replacement);

});