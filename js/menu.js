const menuToggle = document.getElementById("menu-toggle");
const navUl = document.querySelector("#nav ul");

menuToggle.addEventListener("click", () => {
  const open = navUl.classList.toggle("active");
  menuToggle.setAttribute("aria-expanded", open);
});
