<?php
//
// Carga la configuración del servidor
$ApiName = '';
if( !empty($_ENV['API_NAME']) )
{
	$ApiName = $_ENV['API_NAME'];
	$ConfigPath = "servidor.{$ApiName}.ini";
}
else
{
	$ConfigPath = 'servidor.ini';
}
//print("Cargando configuración desde {$ConfigPath}".PHP_EOL);
//$Config= parse_ini_file('..' .DIRECTORY_SEPARATOR. $ConfigPath,true);
$Config= parse_ini_file($ConfigPath,true);


?>
<html>
	<head>
		<style>
			body {
				background-image: url(./cs_bg.jpg);
				background-repeat: repeat-x;
			}
			#container {
				clear: both;
				width: 90%;
				max-width: 780px;
				__height: 90%;
				__min-height: 475px;
				background-color: white;
				margin: 30px auto 0;
				padding: 20px;

				-moz-border-radius: 30px;
			}
			h1 {
				color: #336699;
				font-family: "Trebuchet MS",Helvetica,Arial,sans-serif;
				margin-top: 0;
				margin-bottom: 0;
				text-decoration: underline;
			}
			h2 {
				color: #88BBFF;
				font-family: "Trebuchet MS",Helvetica,Arial,sans-serif;
				font-style: italic;
				font-size: 0.9em;
			}
			#visitante {
				color: #258;
				font-family: Arial, Helvetica, sans-serif;
				font-size: 1.2em;
				line-height: 1.5em;
			}
			#paraAdmin {
				color: #533;
				font-family: Arial, Helvetica, sans-serif;
				font-size: 0.9em;
			}
			#paraAdmin a {
				color: #322;
				text-decoration: none;
				border-bottom: dashed thin #224;
			}
			#paraAdmin dd {
				margin-top: 0.5em;
				margin-bottom: 1em;
			}
			#sesodi {
				width: 90%;
				max-width: 780px;
				margin: 00px auto;
				padding-right: 30px;
				text-align: right;
				display: block;
				color: #AAA;
				font-family: monospace;
			}
			#sesodi a {
				color: #898;
				text-decoration: none;
				border-bottom: dashed thin #676;
			}
			#logo {
				width: 25%;
				min-width: 65px;
			}
		</style>
	</head>
	<body>
		<div id="container">
			<div id="logo">
				<svg xmlns="http://www.w3.org/2000/svg"
					xml:space="preserve"
					width="5.6773in" height="1.24015in"
					version="1.1"
					style="shape-rendering:geometricPrecision; text-rendering:geometricPrecision; image-rendering:optimizeQuality; fill-rule:evenodd; clip-rule:evenodd"
					viewBoxOriginal="0 0 5705 1246"
					viewBox="0 0 9128 1800"
 					xmlns:xlink="http://www.w3.org/1999/xlink">
 					<defs>
					  <style type="text/css">
					   <![CDATA[
					    .fil0 {fill:#173F5D;fill-rule:nonzero}
					   ]]>
					  </style>
 					</defs>
 					<g id="__x0023_Layer_x0020_1">
  <metadata id="CorelCorpID_0Corel-Layer"/>
  <g id="_816242800">
   <g>
    <path class="fil0" d="M376 797c-60,0 -105,10 -135,30 -31,20 -48,51 -52,92l459 1 0 93 -460 0c0,46 14,79 44,99 28,20 76,30 144,30l272 0 0 93 -280 0c-33,0 -61,-1 -84,-2 -22,-2 -43,-5 -62,-9 -35,-6 -68,-18 -100,-36 -40,-21 -70,-49 -91,-84 -21,-36 -31,-77 -31,-125 0,-93 30,-162 91,-207 61,-45 154,-68 281,-68l276 0 0 93 -272 0z"/>
    <path class="fil0" d="M879 704l0 317c0,49 13,83 40,103 27,20 73,30 137,30 65,0 111,-10 139,-30 26,-19 39,-53 39,-103l0 -317 166 0 0 320c0,19 -1,36 -3,50 -2,15 -5,28 -8,40 -7,23 -19,40 -36,52 -30,25 -69,43 -117,56 -49,12 -109,19 -180,19 -70,0 -129,-6 -176,-18 -48,-11 -88,-30 -121,-56 -15,-13 -26,-30 -34,-51 -7,-22 -11,-53 -11,-91l0 -321 165 0z"/>
    <path class="fil0" d="M1854 799l-203 0 0 134 203 0c54,0 92,-5 115,-15 21,-12 32,-29 32,-53 0,-24 -11,-41 -32,-51 -21,-10 -60,-15 -115,-15zm-369 -95l0 0 409 0c95,0 164,13 208,39 45,26 67,67 67,121 0,40 -14,73 -41,99 -27,26 -67,43 -119,51l198 221 -188 0 -173 -207 -195 0 0 207 -166 0 0 -531z"/>
    <path class="fil0" d="M2577 784c-70,0 -124,15 -161,45 -34,31 -52,76 -52,136 0,65 18,113 52,143 33,30 87,46 161,46 37,0 68,-4 94,-12 26,-7 47,-18 65,-34 35,-30 53,-76 53,-139 0,-63 -17,-109 -52,-139 -35,-31 -88,-46 -160,-46zm-394 180l0 0c0,-91 32,-159 97,-204 64,-44 163,-66 297,-66 133,0 232,22 296,68 64,45 96,114 96,207 0,94 -32,163 -96,209 -65,45 -163,68 -296,68 -137,0 -236,-23 -299,-68 -63,-46 -95,-117 -95,-214z"/>
    <path class="fil0" d="M3295 1014l0 -94 391 0 0 315 -357 0c-64,0 -113,-4 -148,-11 -36,-7 -70,-19 -102,-36 -40,-21 -70,-49 -91,-84 -21,-36 -31,-77 -31,-125 0,-93 31,-162 92,-207 61,-45 154,-68 280,-68l357 0 0 91 -357 0c-63,0 -110,14 -140,42 -30,29 -46,74 -46,137 0,58 15,100 45,126 29,26 76,39 141,39l186 0 0 -125 -220 0z"/>
    <path class="fil0" d="M4059 1235c-74,0 -129,-4 -164,-13 -36,-8 -67,-23 -95,-44 -26,-17 -44,-37 -55,-61 -12,-23 -18,-55 -18,-98l0 -315 167 0 0 311c0,33 2,55 7,67 4,12 10,22 18,28 8,7 18,12 28,17 10,4 21,8 32,11 25,5 57,8 95,8l195 0 0 89 -210 0z"/>
    <path class="fil0" d="M4589 810l-115 204 235 0 -120 -204zm-81 -106l0 0 176 0 336 531 -184 0 -72 -125 -344 0 -70 125 -176 0 334 -531z"/>
    <path class="fil0" d="M5668 799l-410 0c-45,0 -77,5 -97,16 -21,11 -31,29 -31,53 0,19 9,33 26,43 19,10 45,15 78,15l219 0c91,0 156,11 194,34 38,23 58,62 58,117 0,54 -22,94 -66,119 -44,26 -113,39 -209,39l-480 0 0 -95 453 0c48,0 81,-4 99,-14 20,-9 30,-24 30,-46 0,-21 -9,-36 -27,-47 -18,-9 -48,-13 -90,-13l-196 0c-90,0 -157,-12 -200,-37 -41,-25 -62,-64 -62,-116 0,-56 22,-97 67,-124 44,-26 114,-39 209,-39l435 0 0 95z"/>
   </g>
   <path class="fil0" d="M649 423c0,0 975,-429 2182,-423 1207,6 2225,442 2225,442 0,0 -1251,-340 -2236,-352 -984,-11 -2171,333 -2171,333z"/>
  </g>
 </g>
</svg>

			</div>
			<div>
				<h1>Bienvenido a <?php echo $_SERVER['HTTP_HOST']; ?> <small><?php
					echo empty($Config['ServerName'])?'':$Config['ServerName'];
				?></small></h1>
				<h2>Un desarrollo de EUROGLAS&trade;</h2>
			</div>
			<div id="visitante">
				<p>Esta URL no esta diseñada para ser visitada directamente desde el navegador. M&aacute;s bien, deber&iacute;a ser accesada mediante llamadas remotas (por ejemplo: AJAX)</p>
			</div>
			<hr/>
			<div id="paraAdmin">
				<p>Si usted esta tratando de usar nuestra API, pongase en contacto con nuestro departamento de Sistemas para que puedan ayudarle con la información necesaria.</p>
			</div>
		</div>
		<div id="sesodi">
			Desarrollado por <a href="http://www.euroglas.net/" title="EUROGLAS">EUROGLAS</a>
		</div>
	</body>
</html>
