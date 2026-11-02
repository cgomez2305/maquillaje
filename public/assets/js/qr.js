/* Dibuja el QR de configuración del 2FA (qrcode-generator, MIT) como SVG, sin estilos en línea. */
(function () {
  var box = document.querySelector('[data-qr]');
  if (!box || typeof window.qrcode !== 'function') return;
  var qr = window.qrcode(0, 'M');
  qr.addData(box.getAttribute('data-qr'));
  qr.make();
  var n = qr.getModuleCount(), q = 2, size = n + q * 2;
  var ns = 'http://www.w3.org/2000/svg';
  var svg = document.createElementNS(ns, 'svg');
  svg.setAttribute('viewBox', '0 0 ' + size + ' ' + size);
  svg.setAttribute('width', '200'); svg.setAttribute('height', '200');
  svg.setAttribute('shape-rendering', 'crispEdges');
  var bg = document.createElementNS(ns, 'rect');
  bg.setAttribute('width', size); bg.setAttribute('height', size); bg.setAttribute('fill', '#FFFFFF');
  svg.appendChild(bg);
  var d = '';
  for (var r = 0; r < n; r++) for (var c = 0; c < n; c++) if (qr.isDark(r, c)) d += 'M' + (c + q) + ' ' + (r + q) + 'h1v1h-1z';
  var path = document.createElementNS(ns, 'path');
  path.setAttribute('d', d); path.setAttribute('fill', '#171312');
  svg.appendChild(path);
  box.appendChild(svg);
})();
