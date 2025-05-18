<?php
// Este script crea una imagen predeterminada para los usuarios sin foto de perfil
$width = 200;
$height = 200;
$image = imagecreatetruecolor($width, $height);

// Colores
$background = imagecolorallocate($image, 44, 62, 80); // Color oscuro que combina con el tema
$text_color = imagecolorallocate($image, 255, 255, 255);

// Rellenar el fondo
imagefilledrectangle($image, 0, 0, $width, $height, $background);

// Añadir texto "Usuario"
$font_size = 5;
$text = "  0, 0, $width, $height, $background);

// Añadir texto "Usuario"
$font_size = 5;
$text = "Usuario";
$text_width = imagefontwidth($font_size) * strlen($text);
$text_height = imagefontheight($font_size);
$x = ($width - $text_width) / 2;
$y = ($height - $text_height) / 2;
imagestring($image, $font_size, $x, $y, $text, $text_color);

// Asegurarse de que existe el directorio
$directory = 'uploads/profiles/';
if (!file_exists($directory)) {
    mkdir($directory, 0777, true);
}

// Guardar la imagen
imagepng($image, $directory . 'default.jpg');
imagedestroy($image);

echo "Imagen predeterminada creada con éxito.";
?>
