/* Spec 041 (D19): botón para ver la contraseña del inicio de sesión. Mejora progresiva: el botón nace
   oculto y este script lo muestra, así que sin JavaScript la contraseña se escribe igual. */
(function () {
    const boton = document.querySelector('.ver-clave');
    if (!boton) {
        return;
    }

    const campo = document.getElementById(boton.getAttribute('aria-controls'));
    if (!campo) {
        return;
    }

    boton.hidden = false;
    boton.addEventListener('click', function () {
        const ver = campo.type === 'password';
        campo.type = ver ? 'text' : 'password';
        boton.setAttribute('aria-pressed', String(ver));
    });
})();
