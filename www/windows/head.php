<?php
// head.php
//
// kopf fuer kleine popup-Fenster
// - $title (<title>) und $subtitle (im Fenster) werden angezeigt
// - ein "Close" Knopf wird automatisch erzeugt

  global $angemeldet, $login_gruppen_name, $coopie_name
       , $dienst, $title, $subtitle, $wikitopic, $onload_str, $readonly
       , $kopf_schon_ausgegeben, $print_on_exit
       , $foodsoftpath, $area;

if( isset( $kopf_schon_ausgegeben ) && $kopf_schon_ausgegeben )
  return;

if( ! $title ) $title = "FC Nahrungskette - Foodsoft";
if( ! $subtitle ) $subtitle = "FC Nahrungskette - Foodsoft";
$img = "$foodsoftdir/img/close_black_trans.gif";

if( $readonly ) {
  $headclass='headro';
  $payloadclass='payloadro';
} else {
  $headclass='head';
  $payloadclass='payload';
}
?><!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">
<html>
<head>
  <title id='title'><? echo $title; ?></title>
  <meta http-equiv='Content-Type' content='text/html; charset=utf-8' >
  <link rel='stylesheet' type='text/css' href='<? echo $foodsoftdir; ?>/css/foodsoft.css'>
  <script type='text/javascript' src='<? echo $foodsoftdir; ?>/js/foodsoft.js' language='javascript'></script>	 
</head>
<body>
  <div id='header' style='padding:0.5ex 1em 0.5ex 1ex;margin:0pt 0pt 1em 0pt;' class='<? echo $headclass; ?>'>
    <table width='100%' class='<? echo $headclass; ?>'>
      <tr>
        <td style='padding-right:0.5ex;'>
          <img src='<? echo $img; ?>' class='button' alt='Schlie&szlig;en' title='Schlie&szlig;en'
           width='15' onClick='if(opener) opener.focus(); window.close();'></img>
        </td>
        <td id='subtitle'>Foodsoft: <? echo $subtitle; ?></td>
        <td>
          <? wikiLink( ( $area ? "foodsoft:$area" : 'start' ) , "Hilfe-Wiki...", true ); ?>
        </td>
      </tr>
      <tr>
        <td></td>
        <td style='font-size:11pt;'>
          <?
            if( $angemeldet ) {
              if( $dienst > 0 ) {
                echo "$coopie_name ($login_gruppen_name) / Dienst $dienst";
              } else {
                echo "angemeldet: $login_gruppen_name";
              }
            }
            if( $readonly ) {
              echo "<span style='padding-left:3em;'>schreibgeschuetzt!</span>";
            }
          ?>
        </td>
      </tr>
    </table>
  </div>
  <div id='payload' class='<? echo $payloadclass; ?>'>
<?
$print_on_exit = "</div></body></html>";
$kopf_schon_ausgegeben = true;
?>
