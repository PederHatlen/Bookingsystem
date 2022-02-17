var innhold = document.getElementById("innhold");
var romValg = document.getElementById("romvalg");
var kalender = document.getElementById("kalender");

var valg = document.getElementById("valg");
var dato = document.getElementById("dato");
var fraTid = document.getElementById("fraTid");
var tilTid = document.getElementById("tilTid");

var bookKnapp = document.getElementById("bookKnapp");
var redigerKnapp = document.getElementById("redigerKnapp");
var bekreftelse = document.getElementById("bekreftelse");

function VelgRom() {
    
    kalender.classList.remove("skjul");
    valg.classList.remove("skjul");
}

function BookRom() {

    var popup;

    if (dato.value != 0 && (fraTid.value != 0 && tilTid.value != 0)) {
        popup = window.confirm("Vil du booke " + romValg.value + " denne dagen? Trykk 'OK' for å bekrefte.");
    } else {
        bekreftelse.innerHTML = "Oisann! Det ser ut som at du har glemt å sette inn dato og/eller tidspunkt for booking av rommet. Vennligst sett inn manglende informasjon og prøv på nytt. Takk!"
    }

    if (fraTid.value == tilTid.value) {
        popup = window.alert("Husk at start- og sluttidspunktet ikke kan være like!");
    }

    if (fraTid.value > tilTid.value) {
        popup = window.alert("Husk at start tidspunktet ikke kan være større en sluttidspunktet!");
    }

    if (popup) {
        kalender.classList.add("skjul");
        bookKnapp.classList.add("skjul");
        redigerKnapp.classList.remove("skjul");

        dato.disabled = true;
        fraTid.disabled = true;
        tilTid.disabled = true;

        bekreftelse.innerHTML = "Du booket " + romValg.value + " for den " + dato.value + " fra kl. " + fraTid.value + " til kl. " + tilTid.value;
    } else {

    }

}

function RedigerValg() {

    kalender.classList.remove("skjul");
    bookKnapp.classList.remove("skjul");
    redigerKnapp.classList.add("skjul");

    dato.disabled = false;
    fraTid.disabled = false;
    tilTid.disabled = false;

    bekreftelse.innerHTML = "";

}