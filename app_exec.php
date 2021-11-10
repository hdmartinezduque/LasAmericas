<?php
include_once('funcionesSQL.php');

$emp=01;
$bd='movhos';
$tipTrans='C';
$wemp='01';
$fecDispensacion='2021-11-08';
$cco['cod']=1050;
$historia='918140';
$ing= 1;
$usuario='un.1050';
$fechorDispensacion='2021-10-27 09:28:54';
$alp='on';
$cargarmmq='off';
//$artcod='N2AX11';
$wmovhos = 'movhos';
$date = date('Y-m-d');
if(isset($historia))
{
	$pac['his']=$historia;
}

if(isset($artcod))
{
	$art['cod']=strtoupper( $artcod );
}



//$pac2 = $pac;
//Funciones::esKE($pac, $conex, $bd);
//Funciones::esKE( $pac, $conex, $wmovhos );
//getCco($cco,$tipTrans, $emp);

$fuente = $cco['fue'];

//$pac['act'] = infoPacientePrima($pac,$emp);

//$kardexActual = consultarKardexPorFechaPaciente($wfecha, $paciente);
//$paciente = consultarInfoPacienteKardex($historia,$ing);
//print $paciente->habitacionActual;
//Historia

$sql_pac = Funciones::ConsultaPac($historia, $ing, $conex);
$Regnum = mysql_num_rows ($sql_pac);

print  $Regnum.'<br>';
$pac = mysqli_fetch_array($sql_pac, MYSQLI_ASSOC);

print json_encode($pac);
print '<br>';
//Archivo Logica Funcional 

//$sql_res = Funciones::consultarArticulo($conex, $bd, $artcod);
//$Regnum = mysql_num_rows ($sql_res); 
//$articulos = mysqli_fetch_array($sql_res, MYSQLI_ASSOC);

//print json_encode($articulos);
print '<br>';
//print json_encode($articulos);
/*$pos = 0;
while ($articulos = mysqli_fetch_array($sql_res, MYSQLI_ASSOC))
{
    print ' | '.$pos.' | Codigo | '.$articulos['Artcod'].' | Nombre | '.$articulos['Artcom'].' | <br>';
    $pos = $pos+1;
}
*/
//Consulta Materiales a dispensar por historia

$sql_materiales = Funciones::consultaMateriales($conex, $bd, $historia, $ing);
$Regnum = mysql_num_rows($sql_materiales);

$head_table ='<table border = 1>';
$head_table .='<tr>';
$head_table .='<td>Item</td>';
$head_table .='<td>Kadart</td>';
$head_table .='<td>Kadcma</td>';
$head_table .='<td>Kadcma</td>';
$head_table .='<td>Kaduma</td>';
$head_table .='<td>Kaddia</td>';
$head_table .='<td>Kaddia</td>';
$head_table .='<td>Kadest</td>';
$head_table .='<td>Kadess</td>';
$head_table .='<td>Kadper</td>';
$head_table .='<td>Kadfin</td>';
$head_table .='<td>Kadhin</td>';
$head_table .='<td>Kadvia</td>';
$head_table .='<td>Kadare</td>';
$head_table .='</tr>';
$foot_table = '</table>';
$pos = 0;
if($Regnum >0){
    //$materiales = mysqli_fetch_array($sql_materiales, MYSQLI_ASSOC);
    while($materiales = mysqli_fetch_array($sql_materiales, MYSQLI_ASSOC)){

        $con_table .= '<tr>';
        $con_table .='<td>'.$pos.'</td>';
        $con_table .='<td>'.$materiales['Kadart'].'</td>';
        $con_table .='<td>'.$materiales['Kadcma'].'</td>';
        $con_table .='<td>'.$materiales['Kadcma'].'</td>';
        $con_table .='<td>'.$materiales['Kaduma'].'</td>';
        $con_table .='<td>'.$materiales['Kaddia'].'</td>';
        $con_table .='<td>'.$materiales['Kaddia'].'</td>';
        $con_table .='<td>'.$materiales['Kadest'].'</td>';
        $con_table .='<td>'.$materiales['Kadess'].'</td>';
        $con_table .='<td>'.$materiales['Kadper'].'</td>';
        $con_table .='<td>'.$materiales['Kadfin'].'</td>';
        $con_table .='<td>'.$materiales['Kadhin'].'</td>';
        $con_table .='<td>'.$materiales['Kadvia'].'</td>';
        $con_table .='<td>'.$materiales['kadare'].'</td>';
        $con_table .= '</tr>';

        //print 'Posicion '.$pos.'Articulo '.$materiales['Kadart'].'<br>';
        $pos = $pos+1;

    }

}else{
    $materiales = array("Mensaje" => "No hay materiales a dispensar");
}
//print json_encode($materiales);
print $Regnum.'<br>';
print $head_table.$con_table.$foot_table;
Print '<br>';

$paciente['historiaClinica'] = $historia;
$paciente['ingresoHistoriaClinica'] = $ing;
$paciente['enCirugia'] = false;
$paciente['enUrgencias'] = false;

//Print 'Array paciente <br>';
//print var_dump($paciente);


$kardex = Funciones::consultarKardexPorFechaPaciente($date,$paciente, $bd, $conex, $usuario);
print json_encode($kardex);
Print '<br>';

?>