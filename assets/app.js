/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */
const navBars = document.querySelector(".navBars");
const navMenu = document.querySelector(".navMenu");
const navMenuBg = document.querySelector(".navMenuBg");

navBars.addEventListener("click",()=>{
    navMenuClick();
});

navMenuBg.addEventListener("click",()=>{
    navMenuClick();
});

function navMenuClick() {
    navBars.classList.toggle("fa-bars");
    navBars.classList.toggle("fa-close");
    navMenu.classList.toggle("active");
    navMenuBg.classList.toggle("active");
}

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';
