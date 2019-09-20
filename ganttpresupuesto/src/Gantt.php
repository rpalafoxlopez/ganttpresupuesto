<?php

namespace Gantt\GanttPresupuesto;

use Gantt\GanttPresupuesto\Calendar\Calendar;

class Gantt {
    
    var $cal       = null;
    var $data      = array();
    var $first     = false;
    var $last      = false;
    var $options   = array();
    var $cellstyle = false;
    var $blocks    = array();
    var $events    = array();
    var $months    = array();
    var $days      = array();
    var $seconds   = 0;
    var $prioridad = array(0 => 'important' , 1 => 'important' , 2 => 'warning' , 3 => 'urgent');
    
    function __construct($data, $params=array())
    {
        $defaults = array(
          'title'      => false,
          'cellwidth'  => 45,
          'cellheight' => 45,
          'today'      => true,
        );
        
        $this->options = array_merge($defaults, $params);
        $this->cal     = new Calendar();
        $this->data    = $data;
        $this->seconds = 60*60*24;
        
        $this->cellstyle = 'style="width: ' . $this->options['cellwidth'] . 'px; height: ' . $this->options['cellheight'] . 'px"';

        // parse data and find first and last date
        $this->parse();
        
    }
    
    function parse()
    {

           if($this->data !== NULL)
           {
               foreach($this->data as $d)
               {
                   $this->blocks[] = array(
                       'label' => $d['label'],
                       'cabeceras' => $d['cabeceras'],
                       'idevento' => $d['idevento'],
                       'events' => $eventos = $d['events'],
                       'start' => $start = strtotime($d['start']),
                       'end'   => $end   = strtotime($d['end']),
                       'tipo'  => $d['tipo'],
                       'subtipo'  => $d['subtipo'],
                   );

                   if ($start) {
                       if (!$this->first || $this->first > $start) {
                           $this->first = $start;
                       }
                       if (!$this->last || $this->last < $end) {
                           $this->last = $end;
                       }
                   }

                   if(is_array($eventos))
                   {

                       for($i=0; $i < sizeof($eventos); $i++)
                       {
                           $fechaInicio = $eventos[$i]['fecha_inicio'];
                           $fechaFin = $eventos[$i]['fecha_fin'];
                           if($eventos[$i]['tipo'] == 'evento')
                           {

                               $this->events[ $d['idevento'] ][] = array(
                                   "idEventoDetalle" => $eventos[$i]['id'] ,
                                   "idEvento" => $eventos[$i]['id_evento'] ,
                                   "status" => $eventos[$i]['status'],
                                   "FechaInicio" => strtotime($fechaInicio),
                                   "FechaFin" => strtotime($fechaFin),
                                   "total" => 0,
                                   "subtotal" => 0,
                                   "iva" => 0,
                                   "tipo" => $eventos[$i]['tipo'],
                               );
                           }
                           else{
                               $this->events[ $d['idevento'] ][] = array(
                                   "idEventoDetalle" =>   $eventos[$i]['id'] ,
                                   "idEvento" =>  $eventos[$i]['id_proveedorpago'] ,
                                   "status" =>  $eventos[$i]['status'],
                                   "FechaInicio" => strtotime($fechaInicio),
                                   "FechaFin" => strtotime($fechaFin),
                                   "total" =>  $eventos[$i]['total'],
                                   "subtotal" =>  $eventos[$i]['subtotal'],
                                   "iva" => $eventos[$i]['iva'],
                                   "tipo" =>  $eventos[$i]['tipo'],
                               );
                           }
                       }
                   }
                   else{
                       $this->events[ $d['idevento'] ] = null;
                   }
               }
           }
            if( $this->first !== false &&  $this->last !== false)
            {
                $this->first = $this->cal->date($this->first);
                $this->last  = $this->cal->date($this->last);
                $current = $this->first->month();
                $lastDay = $this->last->month()->lastDay()->timestamp;
                // build the months
                while($current->lastDay()->timestamp <= $lastDay)
                {
                    $month = $current->month();
                    $this->months[] = $month;
                    foreach($month->days() as $day)
                    {
                        $this->days[] = $day;
                    }
                    $current = $current->next();
                }

            }
            else
            {
                $this->first = $this->cal->date(strtotime("now"));
                $this->last  = $this->cal->date(strtotime("last Monday"));

                $current = $this->first->month();
                $lastDay = $this->last->month()->lastDay()->timestamp;
                // build the months
                while($current->lastDay()->timestamp <= $lastDay)
                {
                    $month = $current->month();
                    $this->months[] = $month;
                    foreach($month->days() as $day) {
                        $this->days[] = $day;
                    }
                    $current = $current->next();
                }
            }
    }

    function render()
    {
        $html = array();
        // common styles
        $cellstyle  = 'style="line-height: ' . $this->options['cellheight']. 'px; height:'.$this->options['cellheight'].'px"';
        $cellstylehead  = 'style="line-height: ' . ($this->options['cellheight'] / 2). 'px; height:'.($this->options['cellheight'] / 2).'px"';
        $wrapstyle  = 'style="width: ' . $this->options['cellwidth'] . 'px"';
        $totalstyle = 'style="width: ' . (count($this->days)*$this->options['cellwidth']) . 'px"';
        // start the diagram
        $html[] = '<figure class="gantt">';
        // set a title if available
        if($this->options['title']) {
            $html[] = '<figcaption>' . $this->options['title'] . '</figcaption>';
        }
        // sidebar with labels
        $html[] = '<aside>';
        $html[] = '<ul class="gantt-labels" style="margin-top: ' . ($this->options['cellheight'] + 5). 'px">';
        $html[] = '<li class="gantt-label"><strong>'. $this->blocks[0]['cabeceras'] .'</strong></li>';

        foreach($this->blocks as $i => $block) {
            $html[] = '<li class="gantt-label"><strong ' . $cellstyle . '>' . $block['label'] . '</strong></li>';
        }
        $html[] = '<li class="gantt-label"><strong ' . $cellstyle . '><div class="col-sm-12 text-right"><b>Subtotal:</b></div></strong></li>';
        $html[] = '<li class="gantt-label"><strong ' . $cellstyle . '><div class="col-sm-12 text-right"><b>IVA:</b></div></strong></li>';
        $html[] = '<li class="gantt-label"><strong ' . $cellstyle . '><div class="col-sm-12 text-right"><b>Total:</b></div></strong></li>';
        $html[] = '</ul>';
        $html[] = '</aside>';
        // data section
        $html[] = '<section class="gantt-data">';
        // data header section
        $html[] = '<header>';
        // months headers
        $html[] = '<ul class="gantt-months" ' . $totalstyle . '>';
        foreach($this->months as $month)
        {
            $html[] = '<li class="gantt-month text-capitalize" style="width: ' . ($this->options['cellwidth'] * $month->countDays()) . 'px"><strong ' . $cellstyle . '>' . $month->name() . '</strong></li>';
        }
        $html[] = '</ul>';

        // days names
        $html[] = '<ul class="gantt-days-names" ' . $totalstyle . '>';
        foreach($this->days as $day)
        {
            $weekend = ($day->isWeekend()) ? ' weekend' : '';
            $today   = ($day->isToday())   ? ' today' : '';
            $html[] = '<li class="gantt-day text-capitalize' . $weekend . $today . '" ' . $wrapstyle . '>'.
                            '<span '.$cellstylehead.'>'.$day->shortname().'</span>'.
                      '</li>';
        }
        $html[] = '</ul>';

        // days headers
        $html[] = '<ul class="gantt-days" ' . $totalstyle . '>';
        foreach($this->days as $day)
        {
            $weekend = ($day->isWeekend()) ? ' weekend' : '';
            $today   = ($day->isToday())   ? ' today' : '';
            $html[] = '<li class="gantt-day text-capitalize' . $weekend . $today . '" ' . $wrapstyle . '>'.
                          '<span '.$cellstylehead.'>'.$day->padded().'</span>'.
                      '</li>';
        }
        $html[] = '</ul>';
        // end header
        $html[] = '</header>';
        
        // main items
        $html[] = '<ul class="gantt-items" ' . $totalstyle . '>';
        $totalpago=[];$subtotalpago=[];$ivatpago=[];

        foreach($this->blocks as $i => $block)
        {
            $html[] = '<li class="gantt-item">';
                $html[] = '<ul class="gantt-days">';
                   $dias = [];$dayexist = [];
                    foreach($this->days as $day)
                    {
                        $pagt = 0;$pagst = 0;$pagit = 0;
                        $weekend = ($day->isWeekend()) ? ' weekend' : '';
                        $today   = ($day->isToday())   ? ' today' : '';
                        $pagt = 0;$totaldia=0;


                        if(sizeof($this->events) > 0)
                        {
                           $dayexist = $this->dayhas($day , $this->events[$block['idevento']] );
                           if($dayexist['exist'])
                           {
                               $tfm = '';
                               if( $dayexist['total'] !== '')
                               {
                                   if(isset($totalpago[$day->timestamp]["totalpago"]))
                                   {
                                      $totalpago[$day->timestamp]["totalpago"] +=  $dayexist['total'];
                                   }else{
                                     $totalpago[$day->timestamp]["totalpago"] = $dayexist['total'];
                                   }

                                   if(isset($subtotalpago[$day->timestamp]["subtotalpago"]))
                                   {
                                       $subtotalpago[$day->timestamp]["subtotalpago"] += $dayexist['subtotal'];
                                   }else{
                                      $subtotalpago[$day->timestamp]["subtotalpago"] = $dayexist['subtotal'];
                                   }

                                   if(isset($ivatpago[$day->timestamp]["ivapago"]))
                                   {
                                       $ivatpago[$day->timestamp]["ivapago"] +=  $dayexist['iva'];
                                   }else{
                                        $ivatpago[$day->timestamp]["ivapago"] = $dayexist['iva'];
                                   }
                                   $tfm = '$'.$dayexist['total'];
                               }

                               $html[] = '<li class="gantt-day' . $weekend . $today . '" ' . $wrapstyle . '>'.
                                    '<a href="javascript:popup('.$block['idevento'].' , '. $dayexist['idpago'].' )">'.
                                    '<span ' . $cellstyle . '></span>'.
                                    '<span class="gantt-block' . $dayexist['class'] . '" style=" width: ' . $this->options['cellwidth'] . 'px; height: ' . $this->options['cellheight'] . 'px"><strong class="gantt-block-label" style="text-indent: 0 !important;padding: 5px 0!important;text-align: center;color:#fff;"> '. $tfm .' </strong>'.
                                    '</span>'.
                                    '</a>'.
                                    '</li>';
                           }
                           else
                           {
                                $html[] = '<li class="gantt-day' . $weekend . $today . '" ' . $wrapstyle . ' style="border:2px solid #333300;">'.
                                    '<span ' . $cellstyle . '></span>'.
                                    '</li>';
                           }
                        }
                        else
                        {
                            $html[] = '<li class="gantt-day' . $weekend . $today . '" ' . $wrapstyle . ' style="border:2px solid #333300;">'.
                                '<span ' . $cellstyle . '>'.$day.'</span>'.
                                '</li>';
                        }

                    }
               $html[] = '</ul>';
            $html[] = '</li>';
        }

        //*********************************************** TOTALES
        $html[] = '<li class="gantt-item">';
        $html[] = '<ul class="gantt-days">';
        foreach ($this->days as $day) {


           if( isset($subtotalpago[$day->timestamp]["subtotalpago"]) )
           {
               $html[] = '<li class="gantt-day' . $weekend . $today . '" ' . $wrapstyle . '>' .
                   '<a href="javascript:popupay(' . $block['idevento'] . ')">' .
                   '<span ' . $cellstyle . '></span>' .
                   '<span class="gantt-block total" style=" width: ' . $this->options['cellwidth'] . 'px; height: ' . $this->options['cellheight'] . 'px"><strong class="gantt-block-label" style="text-indent: 0 !important;padding: 5px 0!important;text-align: center;color:#333;"> $'.$subtotalpago[$day->timestamp]["subtotalpago"].'</strong>' .
                   '</span>' .
                   '</a>' .
                   '</li>';
           } else{
               $html[] = '<li class="gantt-day error' . $weekend . $today . '" ' . $wrapstyle . ' style="border:2px solid #333300;">'.
                   '<span ' . $cellstyle . '></span>'.
                   '</li>';
           }

        }
        $html[] = '</ul>';
        $html[] = '</li>';

        $html[] = '<li class="gantt-item">';
        $html[] = '<ul class="gantt-days">';
        foreach ($this->days as $day) {
           if( isset($ivatpago[$day->timestamp]["ivapago"]) )
           {
               $html[] = '<li class="gantt-day' . $weekend . $today . '" ' . $wrapstyle . '>' .
                   '<a href="javascript:popupay(' . $block['idevento'] . ')">' .
                   '<span ' . $cellstyle . '></span>' .
                   '<span class="gantt-block total" style=" width: ' . $this->options['cellwidth'] . 'px; height: ' . $this->options['cellheight'] . 'px"><strong class="gantt-block-label" style="text-indent: 0 !important;padding: 5px 0!important;text-align: center;color:#333;"> $'.$ivatpago[$day->timestamp]["ivapago"].'</strong>' .
                   '</span>' .
                   '</a>' .
                   '</li>';
           } else{
               $html[] = '<li class="gantt-day error' . $weekend . $today . '" ' . $wrapstyle . ' style="border:2px solid #333300;">'.
                   '<span ' . $cellstyle . '></span>'.
                   '</li>';
           }
        }
        $html[] = '</ul>';
        $html[] = '</li>';

        $html[] = '<li class="gantt-item">';
        $html[] = '<ul class="gantt-days">';
        foreach ($this->days as $day) {
            if( isset($totalpago[$day->timestamp]["totalpago"]) )
            {
                $html[] = '<li class="gantt-day' . $weekend . $today . '" ' . $wrapstyle . '>' .
                    '<a href="javascript:popupay(' . $block['idevento'] . ')">' .
                    '<span ' . $cellstyle . '></span>' .
                    '<span class="gantt-block total" style=" width: ' . $this->options['cellwidth'] . 'px; height: ' . $this->options['cellheight'] . 'px"><strong class="gantt-block-label" style="text-indent: 0 !important;padding: 5px 0!important;text-align: center;color:#333;"> $'.$totalpago[$day->timestamp]["totalpago"].'</strong>' .
                    '</span>' .
                    '</a>' .
                    '</li>';
            }
            else{
                $html[] = '<li class="gantt-day error' . $weekend . $today . '" ' . $wrapstyle . ' style="border:2px solid #333300;">'.
                    '<span ' . $cellstyle . '></span>'.
                    '</li>';
            }

        }
        $html[] = '</ul>';
        $html[] = '</li>';

        //*********************************************** TOTALES

        $html[] = '</ul>';

        if($this->options['today'])
        {
            $today  = $this->cal->today();
            $offset = (($today->timestamp - $this->first->month()->timestamp) / $this->seconds);
            $left   = round($offset * $this->options['cellwidth']) + round(($this->options['cellwidth'] / 2) - 1);
            if($today->timestamp > $this->first->month()->firstDay()->timestamp && $today->timestamp < $this->last->month()->lastDay()->timestamp) {
                $html[] = '<time style="top: ' . ($this->options['cellheight'] * 2) . 'px ; left: ' . $left . 'px" datetime="' . $today->format('Y-m-d') . '">Today</time>';
            }
        }
        // end data section
        $html[] = '</section>';
        // end diagram
        $html[] = '</figure>';

        return implode('', $html);
    }

    function dayhas($dia , $eventos)
    {
        $ban = false;
        $class = '';
        $hoydia = strtotime(date("Y-m-d", $dia->timestamp ));
        $total = '';$subtotal = '';$iva = '';$idpago = 0;
        if(!empty($eventos))
        {
            for($i=0; $i < sizeof($eventos); $i++ )
            {
                $fechainicio = strtotime(date("Y-m-d", $eventos[$i]['FechaInicio'] ));
                $fechafin = strtotime(date("Y-m-d", $eventos[$i]['FechaFin'] ));
                $class  = ' '.$this->prioridad[$eventos[$i]['status']];
                if( $hoydia == $fechainicio  &&   $fechafin == $hoydia)
                {
                    $ban = true;
                   if($eventos[$i]["tipo"] == 'pago')
                   {
                       $total = $eventos[$i]["total"];
                       $subtotal = $eventos[$i]["subtotal"];
                       $iva = $eventos[$i]["iva"];
                       $idpago = $eventos[$i]['idEventoDetalle'];
                   }
                }
            }
            return ["exist" => $ban, "class" => $class, "total" => $total , "subtotal" => $subtotal , "iva" => $iva, 'idpago' => $idpago];
        }
        else{
            return ["exist" => $ban, "class" => $class, "total" => $total , "subtotal" => $subtotal , "iva" => $iva, 'idpago' => $idpago];
        }
    }

    function dayhasevent($dia , $eventos)
    {
        $ban = false;
        $class = '';
        $hoydia = strtotime(date("Y-m-d", $dia->timestamp ));
        if(!empty($eventos))
        {
            foreach ( $eventos as $event )
            {
                $fechainicio = strtotime(date("Y-m-d", $event['FechaInicio'] ));
                $fechafin = strtotime(date("Y-m-d", $event['FechaFin'] ));
                $class  = ' '.$this->prioridad[$event['status']];
                if( $hoydia == $fechainicio  &&   $fechafin == $hoydia)
                {
                    $ban = true;
                }
            }
            return ["exist" => $ban, "class" => $class];
        }
        else{
            return ["exist" => $ban, "class" => $class];
        }

    }
    function dayhaspay($dia , $eventos)
    {
        $ban = false;
        $class = '';
        $hoydia = strtotime(date("Y-m-d", $dia->timestamp ));
        $total = '';$subtotal = '';$iva = '';
        if(!empty($eventos))
        {
            foreach ( $eventos as $event )
            {
                $fechainicio = $event['FechaInicio'];
                $fechafin = $event['FechaFin'];
                $class  = ' '.$this->prioridad[$event['status']];
                if( $hoydia == $fechainicio  &&   $fechafin == $hoydia)
                {
                    $ban = true;
                    $total = $event["total"];
                    $subtotal = $event["subtotal"];
                    $iva = $event["iva"];
                }
            }
            return ["exist" => $ban, "class" => $class, "total" => $total , "subtotal" => $subtotal , "iva" => $iva];
        }
        else{
            return ["exist" => $ban, "class" => $class, "total" => $total , "subtotal" => $subtotal , "iva" => $iva];
        }

    }
    
    function __toString()
    {
        return $this->render();
    }
    
}