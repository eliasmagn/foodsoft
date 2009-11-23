<?php
//
// abrechnung.php:
//

assert( $angemeldet ) or exit();

// flags:
//   $teil_abrechnung: nur teil einer gesamtabrechnung: nicht edierbar!
//   $gesamt_abrechnung: gesamtsicht ueber mehr als eine teil-abrechnung

get_http_var( 'abrechnung_id', 'u', 0, true );
if( $abrechnung_id ) {
  $teil_abrechnung = false;
  $gesamt_abrechnung = true;
  $bestell_id = $abrechnung_id;  // group leader...
} else {
  need_http_var( 'bestell_id', 'U', true );
  $bestellung = sql_bestellung( $bestell_id );
  $abrechnung_id = $bestellung['abrechnung_id'];
  $teil_abrechnung = true;
  $gesamt_abrechnung = false;
}

$bestell_id_set = sql_abrechnung_set( $abrechnung_id );
$bestell_id_count = count( $bestell_id_set );
if( $bestell_id_count == 1 ) {
  $teil_abrechnung = false;
  $gesamt_abrechnung = false;
}

$bestellung = sql_bestellung( $bestell_id );
$bestellung_name = $bestellung['name'];
$lieferant_id = $bestellung['lieferanten_id'];
$lieferant = sql_lieferant( $lieferant_id );
$lieferant_name = $lieferant['name'];

foreach( $bestell_id_set as $bestell_id ) {
  $status = sql_bestellung_status( $bestell_id );
  need( $status >= STATUS_VERTEILT, "Bestellung ist noch nicht verteilt!" );
  need( $status < STATUS_ARCHIVIERT, "Bestellung ist bereits archiviert!" );
  need( sql_bestellung_lieferant_id( $bestell_id ) == $lieferant_id, "Inkonsistenz: Bestellungen verschiedener Lieferanten sind zusammengefasst" );
}

$editable = ( hat_dienst(4)
          and ! $readonly
          and ! $teil_abrechnung
          and ( $status < STATUS_ABGERECHNET ) );


setWikiHelpTopic( 'foodsoft:Abrechnung' );


//
// aktionen verarbeiten:
//
/////////////////////////////

get_http_var( 'action', 'w', '' );
$editable or $action = '';

if( $action == 'save' ) {
  if( $status == STATUS_ABGERECHNET ) {
    need_http_var( 'rechnung_abschluss', 'w', '' );
    if( $rechnung_abschluss == 'reopen' ) {
      sql_change_bestellung_status( $bestell_id, STATUS_VERTEILT );
    }
  } else if( $status < STATUS_ABGERECHNET ) {
    need_http_var( 'rechnungsnummer', 'H' );
    need_http_var( 'extra_text', 'H' );
    need_http_var( 'extra_soll', 'f' );
    need_http_var( 'aufschlag', 'f' );
    sql_update( 'gesamtbestellungen', $bestell_id, array(
      'rechnungsnummer' => $rechnungsnummer
    , 'extra_text' => $extra_text
    , 'extra_soll' => $extra_soll
    ) );
    get_http_var( 'rechnung_abschluss', 'w', '' );
    if( $rechnung_abschluss == 'yes' ) {
      need( abs( basar_wert_brutto( $bestell_id ) ) < 0.01 , "Abschluss noch nicht möglich: da sind noch Reste im Basar!" );
      sql_change_bestellung_status( $bestell_id, STATUS_ABGERECHNET );
    }
  }
}


$gruppenpfand = current( sql_gruppenpfand( $lieferant_id, $bestell_id, "gesamtbestellungen.id" ) ); // TODO

$lieferanten_soll = sql_bestellung_soll_lieferant( $bestell_id ); // TODO

$warenwert_verteilt_brutto = - sql_verteilt_brutto_soll( $bestell_id ); 
$warenwert_muell_brutto = - sql_muell_brutto_soll( $bestell_id ); 
$warenwert_basar_brutto = basar_wert_brutto( $bestell_id ); 

$aufschlag_soll = sql_aufschlag_soll( $bestell_id );



if( $bestell_id_count > 1 )
  abrechnung_overview( $abrechnung_id );



if( $gesamtabrechnung ) {
  open_fieldset( '', "'style='padding:1em;'", "Gesamt-Abrechnung: $bestell_id_count Bestellungen"
} else {
  open_fieldset( '', "'style='padding:1em;'", "Abrechnung: Bestellung $bestellung_name"
                                   . fc_link( 'edit_bestellung', "bestell_id=$bestell_id" )
                                   . " / Lieferant: " .lieferant_view( $lieferant_id ) );
}

if( hat_dienst(4) and ! $readonly )
  open_form( '', 'action=save' );

open_table( 'list', "style='width:98%'" );
  open_th( '', '', 'Abrechnungsschritt' );
  open_th( '', '', 'Details' );
  open_th( '', '', 'Netto' );
  open_th( '', '', 'Brutto' );
  open_th( '', '', 'Aktionen' );

  //
  // gruppenteil:
  //
  open_tr();
    open_th( '', "colspan='5' style='padding-top:2em;'", 'Bestellgruppen:' );

  open_tr();
    open_td( '', '', 'Basarkäufe eintragen:' );
    open_td( '', '', 'Reste im Basar:' );
    open_td();
    open_td( 'bold number', '', price_view( $warenwert_basar_brutto ) );
    open_td( 'bottom', '', fc_link( 'basar', "text=zum Basar...,class=href" ) );
  open_tr();
    open_td( '', "rowspan='2'", "Verteilmengen abgleichen:" );
    open_td( 'right', '', 'Warenwert Gruppen:' );
    open_td();
    open_td( 'bold number', '', price_view( $warenwert_verteilt_brutto ) );
    open_td( 'vcenter', "rowspan='2'" );
      if( hat_dienst( 1,3,4,5 ) )
        echo fc_link( 'verteilliste', "bestell_id=$bestell_id,text=zur Verteilliste...,class=href" );
      else
        echo fc_link( 'lieferschein', "bestell_id=$bestell_id,text=zum Lieferschein...,class=href" );
  open_tr();
    open_td( 'right', '', 'auf den Müll gewandert:' );
    open_td();
    open_td( 'bold number', '', price_view( $warenwert_muell_brutto ) );

  open_tr( 'summe' );
    open_td( '', "colspan='3'", 'Summe' );
    open_td( 'number', '', price_view( $warenwert_verteilt_brutto + $warenwert_muell_brutto + $warenwert_basar_brutto ) );
    open_td();

if( $lieferant['anzahl_pfandverpackungen'] > 0 ) {
  open_tr();
    open_td( '', "rowspan='2'", 'Pfandabrechnung Bestellgruppen:' );
    open_td( 'right', '', 'berechnet (Kauf):' );
    open_td();
    open_td( 'bold number', '', price_view( -$gruppenpfand['pfand_voll_brutto_soll'] ) );
    open_td( 'vcenter', "rowspan='2'",
             fc_link( 'gruppenpfand', "bestell_id=$bestell_id,class=href,text=zur Pfandabrechnung..." ) );
  open_tr();
    open_td( 'right', '', 'gutgeschrieben (Rückgabe):' );
    open_td();
    open_td( 'bold number', '', price_view( -$gruppenpfand['pfand_leer_brutto_soll'] ) );
}

if( $bestellung['aufschlag_prozent'] > 0 ) {
  // open_tr();
  //  open_th( '', "colspan='5'" );
  open_tr();
    open_td( '', "colspan='3'"
      , "Aufschlag " . price_view( $bestellung['aufschlag_prozent'], ( $editable ? 'aufschlag' : false ) ) . "% fuer Bestellgruppen:"
    );
    open_td( 'bold number', '', price_view( -$aufschlag_soll ) );
    open_td( '', "colspan='1'" );
}

  //
  // lieferantenteil:
  //
  open_tr();
    open_th( 'bigskip', "colspan='5'" );
      echo "Lieferant: $lieferant_name";
      open_div( 'oneline' );
        ?> Rechnungsnummer des Lieferanten: <?  qquad();
        echo string_view( $bestellung['rechnungsnummer'], 40, ( $editable ? 'rechnungsnummer' : false ) );
      close_div();

  open_tr();
    open_td( '', '', 'Liefermengen und -preise abgleichen:' );
    open_td( 'right', '', 'Warenwert:' );
    open_td( 'bold number', '', price_view( $lieferanten_soll['waren_netto_soll'] ) );
    open_td( 'bold number', '', price_view( $lieferanten_soll['waren_brutto_soll'] ) );
    open_td( 'bottom', '', fc_link( 'lieferschein', "bestell_id=$bestell_id,class=href,text=zum Lieferschein..." ) );

if( $lieferant['anzahl_pfandverpackungen'] > 0 ) {
  open_tr();
    open_td( '', "rowspan='2'", "Pfandabrechnung Lieferant: <div class='small'>(falls zutreffend, etwa bei Terra!)</div>" );
    open_td( 'right', '', 'berechnet (Kauf):' );
    open_td( 'bold number', '', price_view( $lieferanten_soll['pfand_voll_netto_soll'] ) );
    open_td( 'bold number', '', price_view( $lieferanten_soll['pfand_voll_brutto_soll'] ) );
    open_td( 'vcenter', "rowspan='2'"
      , fc_link( 'pfandzettel', "bestell_id=$bestell_id,lieferanten_id=$lieferant_id,class=href,text=zum Pfandzettel..." ) );

  open_tr();
    open_td( 'right', '', 'gutgeschrieben (Rückgabe):' );
    open_td( 'bold number', '', price_view( $lieferanten_soll['pfand_leer_netto_soll'] ) );
    open_td( 'bold number', '', price_view( $lieferanten_soll['pfand_leer_brutto_soll'] ) );
}
  open_tr( 'summe' );
    open_td( '', "colspan='2'", 'Zwischensumme:' );
    open_td( 'number', '', price_view( $lieferanten_soll['waren_netto_soll']
                                       + $lieferanten_soll['pfand_leer_netto_soll']
                                       + $lieferanten_soll['pfand_voll_netto_soll'] ) );
    open_td( 'number', '', price_view( $lieferanten_soll['waren_brutto_soll']
                                       + $lieferanten_soll['pfand_leer_brutto_soll']
                                       + $lieferanten_soll['pfand_voll_brutto_soll'] ) );
    open_td( '', "colspan='2'" );

  open_tr();
    open_td( '', "colspan='3'" );
      ?> Sonstiges: <? qquad();
      echo string_view( $bestellung['extra_text'], 40, ( $editable ? 'extra_text' : false ) );
    open_td( 'number bottom', ''
      , price_view( $bestellung['extra_soll'], ( $editable ? 'extra_soll' : false ) ) );

  open_tr( 'summe' );
    open_td( '', "colspan='3'", 'Summe:' );
    open_td( 'number', '', price_view( sql_bestellung_rechnungssumme( $bestell_id ) ) );
    open_td();

  open_tr();
    if( $status >= STATUS_ABGERECHNET ) {
      open_td( 'right medskip', "colspan='5'" );
        ?> Abrechnung durchgeführt: <?
         echo sql_dienstkontrollblatt_name( $bestellung['abrechnung_dienstkontrollblatt_id'] ) .", "
              . $bestellung['abrechnung_datum'];
        if( hat_dienst(4) && ( $status == STATUS_ABGERECHNET ) ) {
          qquad();
          echo "Nochmal öffnen:
            <input type='checkbox' name='rechnung_abschluss' value='reopen' $input_event_handlers>";
          qquad();
          submission_button();
        }
    } else {
      if( hat_dienst(4) ) {
        if( abs( $warenwert_basar_brutto ) < 0.05 ) {
          open_td( 'medskip right', "colspan='4' style='border-right:none;'"
                   , "Rechnung abschliessen:
                     <input type='checkbox' name='rechnung_abschluss' value='yes' $input_event_handlers>" );
        } else {
          open_td( 'medskip left smaller', "colspan='4' style='border-right:none;'"
                   , " Reste im Basar --- bitte vor Abschluss leermachen!" );
        }
        open_td( 'right bottom medskip', "style='border-left:none;'" );
          submission_button();
      }
    }

close_table();

if( hat_dienst(4) and ! $readonly )
  close_form();

close_fieldset();

?>
