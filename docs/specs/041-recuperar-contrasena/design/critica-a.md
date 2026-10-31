# Crítica · opción a · ronda 1
Inspector: 0 FAIL · 0 WARN (anchos 320, 390, 900 y 1440; modo operar; Playwright 1.63.0 en Docker). La primera pasada dio WARN `fuente-no-cargada` (Lexend declarada en `comun.css` sin usarse): Lexend pasa a cargarse solo en C.
| Pase | Nota 0–10 | Qué se ve (captura) |
|---|---|---|
| Idea: se reconoce el sujeto en la primera pantalla; pasa la prueba del competidor | 6 | La ficha «Qué haremos · Cuánto dura · Tus opciones» abre cada paso y los dos correos (`1440-claro.png`); es el texto propio de la clínica, pero visualmente es una lista etiquetada |
| Cinco segundos: qué es y qué hacer, sin leer el resto | 9 | Título y un solo campo con su botón |
| Jerarquía y escala: una cosa dominante por pantalla | 7 | Título de 28 px dominante; en el escritorio domina el panel vino, como en el login |
| Plenitud: contenido completo; sin tramos vacíos ni secciones delgadas | 8 | Cada estado con su contenido completo; sin tramos vacíos |
| Composición: ninguna sección vecina repite esqueleto; la idea llega al pie | 6 | Repite a propósito la carcasa del login; la ficha llega a los correos |
| Color: la estrategia declarada se ve | 8 | Contenida: rosa solo en etiquetas, enlaces y botón |
| Imágenes | 7 | Solo el logo de la consulta, como en el login |
| Movimiento | 6 | Solo respuesta en los botones (Operar, sobrio) |
| Cosa viva | — | No aplica (Operar) |
| Teléfono: el ancho menor es su propia composición | 8 | Una columna con la ficha en tarjeta; a 320 px no se desborda |
| Reducido y sin JS | 10 | Idéntica: no depende de nada |
| Accesibilidad: contraste, foco visible, orden de lectura | 9 | Botón `--p500` (5.42:1), bordes `--p400` (3.93:1), foco `--p600`; errores con texto e icono |
Tres problemas que más mueven la página: 1. Es la menos memorable (riesgo asumido). 2. La ficha en tres pantallas seguidas puede leerse repetitiva. 3. El texto del panel izquierdo del escritorio es pequeño frente al logo.
