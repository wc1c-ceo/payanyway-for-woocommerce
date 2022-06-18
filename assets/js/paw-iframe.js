/**
 * PAW js iframe
 */

document.addEventListener("DOMContentLoaded", function () {

    document.getElementById("place_order").style.display = "none";

    var paywrapper = document.getElementById("payment");
    var payul = paywrapper.getElementsByTagName("ul");
    if (payul[0] != undefined) {
        payul[0].style.paddingLeft = "0";
    }

    var pawwrapper = document.getElementById("pawiframewrapper");
    var inputs = pawwrapper.innerHTML;

    inputs = '<br/>'
        + '<iframe src="https://www.payanyway.ru/assistant.widget?'
        + inputs
        + '" id="pawrepliframe" frameborder="0" style="margin-top: 15px; background-color: #f7f8f9; border-radius: 10px; width: 100%; min-width: 320px; height: available; min-height: 550px;">'
        + '</iframe>';

    var replacement = document.createElement('pawrepliframe');
    replacement.innerHTML = inputs;

    pawwrapper.replaceWith(replacement);

});