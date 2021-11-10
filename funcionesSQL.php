<?php
include_once("conex.php");
include_once("movhos/validacion_hist.php");
include_once("movhos/fxValidacionArticulo.php");
include_once("movhos/registro_tablas.php");
include_once("movhos/otros.php");
include_once("root/comun.php");
include_once("movhos/cargosSF.inc.php");
include_once("movhos/kardex.inc.php");
include_once("librerias/SQLClass.php");





// Kardex Electronico para el paciente
//Funciones::esKE( $pac, $conex, $wmovhos );
//getCco($cco,$tipTrans, $emp);
//$date = date('Y-m-d');
//$fuente = $cco['fue'];
//$pac['act'] = infoPacientePrima($pac,$emp);
//Variables
//$wmovhos = 'movhos';
//$his = '';
//$ing = '';
//$artcod = '000116';
$CatExcluidos = 'LTQ'.','.'LTR';
///---
//Demo

class Funciones{
	//Consulta Articculos excluidos
	public static function ArticulosExcluidos($conex, $wmovhos, $CatExcluidos)
	{
		$sql = "select Artcom, 	Artuni, Artpos From ".$wmovhos."_000026 Where SUBSTRING_INDEX( Artgru, '-', 1 ) in('".$CatExcluidos."')";
		$res = mysql_query($sql, $conex) or die ("Error: " . mysql_errno() . " - en el query: " . $sql . " - " . mysql_error()); 
		return $res;		
	}

	public static function ConsultaPac($his, $ing, $conex)
	{
		$pac = '';
		$sql = "select cl_100.Pacdoc, cl_100.Pacno1, cl_100.Pacno2, cl_100.Pacap1, cl_100.Pacap2, cl_100.Pacfna, cl_101.Ingnin, cl_101.Ingfei 
		from cliame_000100 As cl_100 Inner Join cliame_000101 As cl_101 On cl_100.Pachis = cl_101.inghis   
		where cl_100.Pachis = '".$his."' AND  cl_101.Ingnin = '".$ing."'";
		$pac = mysql_query($sql, $conex) or die ("Error: " . mysql_errno() . " - en el query: " . $sql . " - " . mysql_error()); 	
		return $pac;
	}

	public static function consultaMateriales($conex, $wmovhos, $his, $ing)
	{
		$sql_materiales = '';
		$sql = "select 
		Kadart
		, Kadcma
		, Kaduma
		, Kaddia
		, Kadest
		, Kadess
		, Kadper
		, Kadfin
		, Kadhin
		, Kadvia
		, Kadare 
		From ".$wmovhos."_000054 Where Kadhis = '".$his."' AND Kading = '".$ing."' And Kadare = 'on'";
		$sql_materiales = mysql_query($sql, $conex) or die ("Error: " . mysql_errno() . " - en el query: " . $sql . " - " . mysql_error()); 
		return $sql_materiales;
		
	}

	public static function consultarArticulo($conex, $wmovhos, $artcod)	
	{
	
		if(strlen($artcod)>0){
   	 		$sql = "select Artcod, Artcom, 	Artuni, Artpos From ".$wmovhos."_000026 Where Artcod = '".$artcod."'";
		}else{
			$sql = "select Artcod, Artcom, 	Artuni, Artpos From ".$wmovhos."_000026 LIMIT 10";
		}


    	$res = mysql_query($sql, $conex) or die ("Error: " . mysql_errno() . " - en el query: " . $sql . " - " . mysql_error()); 
    	return $res;

	}

	public static function consultarDiasDispensacionPorHistoriaIngreso( $conex, $wmovhos, $his, $ing )
	{
	
		$val = 1;
		
		//Consultando el nombre del estudio
		$sql = "SELECT Ccoddi
				  FROM ".$wmovhos."_000011 a, ".$wmovhos."_000018 b
				 WHERE a.ccocod = b.ubisac
				   AND a.ccoest = 'on'
				   AND b.ubihis = '".$his."'
				   AND b.ubiing = '".$ing."'
				 ";
	
		$res = mysql_query($sql, $conex) or die ("Error: " . mysql_errno() . " - en el query: " . $sql . " - " . mysql_error()); 
		
		if( $rows = mysql_fetch_array ($res) ){
			if( !empty($rows['Ccoddi']) )
				$val = $rows['Ccoddi'];
		}
		
		return $val;
	}

	public static function esKE( $pac, $conex, $bd )
	{
	
		global $conex;
		global $bd;
		
		$ke = false;
		$pac['con'] = false;	//Confirmado
		$pac['keact']=false;	//KE Actualizado
		$pac['kegra']=false;	//KE Grabado
		$pac['ke']=false;		//Tiene KE
		
		if( empty($pac['ing']) ){
			return false;
		}
		
		$sql = "SELECT * FROM {$bd}_000053 b  
				WHERE karhis = '{$pac['his']}'";
					
		$restb53 = mysql_query( $sql, $conex );
					
		if( $row1 = mysql_fetch_array($restb53) ){
		
			$pac['ke']=true;
			$ke = true;
		}
		return $pac;
	}

	public static function consultarKardexPorFechaPaciente($wfecha, $paciente, $wbasedato, $conex, $usuario)
	{

	list($historiaClinica, $ingresoHistoriaClinica, $enCirugia, $enUrgencias) = $paciente;
	print $historiaClinica. ' hc';
		//Usuario que ingresa al kardex
	
		$kardex = new kardexDTO();

	
		$kardex->editable = false;
		$kardex->esAnterior = false;
		$kardex->esPrimerKardex = true;
		
		$esFechaActual = ($wfecha == date("Y-m-d"));
	
		//Consulta el kardex del dia seleccionado, pero con el cco de costos no correspondiente al usuario
		//Esto para lugo mirar que el no este abierto por otra personar
		$qOtr = "SELECT
					Fecha_data,Hora_data,Karhis,Karing,Karobs,Karest,Kardia,Karrut,Kartal,Karpes,Karale,Karcui,Karter,Karcon,Karson,Karcur,Karint,Kardec,Kardie,Kardem,Karcip,Kartef,Karrec,Kargra,Karanp,Karais,Karare,Karcco,Karusu,Karmeg,Karprc
				FROM 
					".$wbasedato."_000053
				WHERE
					Karest = 'on'
					AND Fecha_data = '".$wfecha."' 
					AND Karhis = '".$paciente->historiaClinica."'
					AND Karing = '".$paciente->ingresoHistoriaClinica."'
					AND Karcco != '$usuario->centroCostosGrabacion'
					AND Kargra != 'on'
					AND TRIM(karcco) != '';";
				
		//Consulta el kardex del dia seleccionado, si es diferente al dia actual sera de consulta
		$q = "SELECT
				Fecha_data,Hora_data,Karhis,Karing,Karobs,Karest,Kardia,Karrut,Kartal,Karpes,Karale,Karcui,Karter,Karcon,Karson,Karcur,Karint,Kardec,Kardie,Kardem,Karcip,Kartef,Karrec,Kargra,Karanp,Karais,Karare,Karcco,Karusu,Karmeg,Karprc
			FROM 
				".$wbasedato."_000053
			WHERE
				Karest = 'on'
				AND Fecha_data = '".$wfecha."' 
				AND Karhis = '".$paciente->historiaClinica."'
				AND Karing = '".$paciente->ingresoHistoriaClinica."'
				AND Karcco = '$usuario->centroCostosGrabacion';";
	
		$res = mysql_query($q, $conex) or die ("Error: " . mysql_errno() . " - en el query: " . $q . " - " . mysql_error());
		$num = mysql_num_rows($res);
	
		if($num > 0)
		{
			if($esFechaActual)
			{
				$kardex->editable = true;
			}
		}
	
		//Si se consulta el dia actual y no hay kardex del dia actual se consulta el dia anterior
		if($num == 0 && $esFechaActual){
			
			//Consulta el kardex del dia seleccionado, pero con el cco de costos no correspondiente al usuario
			//Esto para lugo mirar que el no este abierto por otra personar
			$qOtr = "SELECT
					Fecha_data,Hora_data,Karhis,Karing,Karobs,Karest,Kardia,Karrut,Kartal,Karpes,Karale,Karcui,Karter,Karcon,Karson,Karcur,Karint,Kardec,Kardie,Kardem,Karcip,Kartef,Karrec,Kargra,Karanp,Karais,Karare,Karcco,Karusu,Karmeg,Karprc
				FROM 
					".$wbasedato."_000053
				WHERE
					Karest = 'on'
					AND Fecha_data = '".$wfecha."' 
					AND Karhis = '".$paciente->historiaClinica."'
					AND Karing = '".$paciente->ingresoHistoriaClinica."'
					AND Karcco != '$usuario->centroCostosGrabacion'
					AND Kargra != 'on'
					AND TRIM(karcco) != '';";
		print $paciente->historiaClinica;			
			$q = "SELECT
					Fecha_data,Hora_data,Karhis,Karing,Karobs,Karest,Kardia,Karrut,Kartal,Karpes,Karale,Karcui,Karter,Karcon,Karson,Karcur,Karint,Kardec,Kardie,Kardem,Karcip,Kartef,Karrec,Kargra,Karanp,Karais,Karare,Karcco,Karusu,Karmeg,Karprc
				FROM 
					".$wbasedato."_000053
				WHERE
					Karest = 'on'
					AND Fecha_data = DATE_SUB('".$wfecha."',INTERVAL 1 DAY) 
					AND Karhis = '".$paciente->historiaClinica."'
					AND Karing = '".$paciente->ingresoHistoriaClinica."'
					AND Karcco = '$usuario->centroCostosGrabacion';";
	
			$res = mysql_query($q, $conex) or die ("Error: " . mysql_errno() . " - en el query: " . $q . " - " . mysql_error());
			$num = mysql_num_rows($res);
	
			if($num > 0 ){
				if($esFechaActual){
					$kardex->editable = true;
					$kardex->esAnterior = true;
				}
			}
		}
	
		if ($num > 0)
		{
			$info = mysql_fetch_array($res);
	
			$kardex->historiaClinica 			= $info['Karhis'];
			$kardex->ingresoHistoriaClinica 	= $info['Karing'];
			$kardex->fechaCreacion 				= $info['Fecha_data'];
			$kardex->horaCreacion 				= $info['Hora_data'];
			$kardex->observaciones 				= $info['Karobs'];
			$kardex->estado 					= $info['Karest'];
			$kardex->diagnostico 				= $info['Kardia'];
			$kardex->rutaOrdenMedica 			= $info['Karrut'];
			$kardex->talla 						= $info['Kartal'];
			$kardex->peso 						= $info['Karpes'];
			$kardex->antecedentesAlergicos 		= $info['Karale'];
			$kardex->cuidadosEnfermeria 		= $info['Karcui'];
			$kardex->terapiaRespiratoria 		= $info['Karter'];
			$kardex->confirmado 				= $info['Karcon'];
			$kardex->sondasCateteres 			= $info['Karson'];
			$kardex->curaciones 				= $info['Karcur'];
			$kardex->interconsulta 				= $info['Karint'];
			$kardex->consentimientos 			= $info['Kardec'];
			$kardex->medidasGenerales 			= $info['Karmeg'];
			$kardex->obsDietas 					= $info['Kardie'];
			$kardex->procedimientos				= $info['Karprc'];
			$kardex->dextrometer 				= $info['Kardem'];
			$kardex->cirugiasPendientes 		= $info['Karcip'];
			$kardex->terapiaFisica 				= $info['Kartef'];
			$kardex->rehabilitacionCardiaca 	= $info['Karrec'];
			$kardex->antecedentesPersonales 	= $info['Karanp'];
			$kardex->aislamientos 				= $info['Karais'];
			$kardex->aprobado 					= $info['Karare'] == 'on' ? true : false;
			$kardex->esPrimerKardex 			= false;
			$kardex->grabado 					= $info['Kargra'];
	
			$kardex->centroCostos 				= $info['Karcco'];
			
			$q1 = "SELECT CONCAT(Codigo,' - ',Descripcion) Usuario FROM usuarios WHERE Codigo = '{$info['Karusu']}'";
			$res1 = mysql_query($q1, $conex) or die ("Error: " . mysql_errno() . " - en el query: " . $q1 . " - " . mysql_error());
			$num1 = mysql_num_rows($res1);
			if($num1 > 0){
				$info1 = mysql_fetch_array($res1);
			}
	//		$paciente = $info1['Usuario'];
	
			$kardex->usuarioQueModifica 		= $info['Karusu'];
			$kardex->nombreUsuarioQueModifica	= $info1['Usuario'];
			
			/****************************************************************************************************
			 * Se revisa que el kardex no este abierto por otra persona
			 ****************************************************************************************************/
			//Se revisa que el kardex no este abierto por otra persona
			$resOtr = mysql_query($qOtr, $conex) or die ("Error: " . mysql_errno() . " - en el query: " . $q1 . " - " . mysql_error());
			
			if( $rowsOtr = mysql_fetch_array( $resOtr ) ){
			
				$kardex->grabado = $rowsOtr['Kargra'];
				
				$q2 = "SELECT CONCAT(Codigo,' - ',Descripcion) Usuario FROM usuarios WHERE Codigo = '{$rowsOtr['Karusu']}'";
				$res2 = mysql_query($q2, $conex) or die ("Error: " . mysql_errno() . " - en el query: " . $q2 . " - " . mysql_error());
				$num2 = mysql_num_rows($res2);
				if($num2 > 0){
					$info2 = mysql_fetch_array($res2);
					$kardex->usuarioQueModifica 		= $rowsOtr['Karusu'];
					$kardex->nombreUsuarioQueModifica	= $info2['Usuario'];
				}
				
			}
			/****************************************************************************************************/
		}
		//****************2010-10-07****Consulto el ultimo movimiento hospitalario
		/*La no acumulacion de saldos depende de lo siguiente:
		 * 0. Los indicadores estan en el objeto paciente y estan marcados como enCirugia y enUrgencias
		 * 1. Si el paciente se encuentra en urgencias o cirugia NO acumula saldos, debido a que no se graba por matrix:
		 * 		Los medicamentos no se envian con el paciente en ninguno de los dos servicios por lo tanto hay un corte en la dispensacion que debe simularse.
		 * 		NOTA:: Cuando el paciente se encuentra en urgencias no hay movimientos en la tabla 17
		 * 2. Si no se encuentra en urgencias o cirugia se debe preguntar por la fecha y hora del ultimo traslado y si el kardex ha sido generado.
		 * 		Si el servicio anterior es urgencias y el encabezado del kardex no ha sido generado consulto
		 */
		$kardex->noAcumulaSaldoDispensacion = false;
		$kardex->descontarDispensaciones = false;
	
		if($paciente->enCirugia || $paciente->enUrgencias){
			$kardex->noAcumulaSaldoDispensacion = true;
		} else {
			/* El Paciente no esta en urgencias ni en cirugia (Los demás servicios acumulan de saldos de dispensación).
			 * 1. Consulta del traslado del dia anterior.
			 * 2. Consulta si el servicio anterior es de urgencias o cirugia
			 * 3. Consulta de la fecha y hora del ultimo traslado para compararlo con la creación de encabezado de kardex
			 */
			$qMv = "SELECT
							".$wbasedato."_000017.Fecha_data,".$wbasedato."_000017.hora_data,Eyrsor,Eyrsde,Eyrhor,Ccourg,Ccocir,Ccoing 
						FROM 
							".$wbasedato."_000017, ".$wbasedato."_000011 
						WHERE 
							Eyrhis = '$paciente->historiaClinica' 
							AND Eyring = '$paciente->ingresoHistoriaClinica' 
							AND Eyrtip = 'Recibo' 
							AND ".$wbasedato."_000017.Fecha_data = DATE_SUB('".$wfecha."',INTERVAL 1 DAY) 
							AND Ccocod = Eyrsor	
							AND Eyrest='on';";
	
			$resMv = mysql_query($qMv,$conex) or die ("Error: " . mysql_errno() . " - en el querys: $qMv - " . mysql_error());
			$contMv = mysql_num_rows($resMv);
			if($contMv>0){
				$infoMv = mysql_fetch_array($resMv);
				//Si hubo traslado el dia anterior, se verifica que sea de urgencias o cirugia
				if(isset($infoMv['Ccourg']) && isset($infoMv['Ccocir']) && isset($infoMv['Ccoing']) && ($infoMv['Ccourg'] == "on" || $infoMv['Ccocir'] == "on" || $infoMv['Ccoing'] == "on")){
					//Si el kardex no fue creado en la misma fecha del traslado, no acumula saldo
					if($kardex->fechaCreacion != $wfecha){
						$kardex->descontarDispensaciones = true; 
						$kardex->noAcumulaSaldoDispensacion = true;	
					} else { //Si el kardex fue creado en la misma fecha del traslado, si acumula saldo
						$kardex->noAcumulaSaldoDispensacion = false;
					}
				} //El paciente en el traslado anterior no estuvo en urgencias o cirugia, necesariamente, debe estar en un serv. hospitalario
				$kardex->horaDescuentoDispensaciones = $infoMv['hora_data'];
			} else {
				//Si no hubo traslado el dia anterior consulto posibles traslados en el dia actual
				$qMv = "SELECT
							".$wbasedato."_000017.Fecha_data,".$wbasedato."_000017.hora_data,Eyrsor,Eyrsde,Eyrhor,Ccourg,Ccocir,Ccoing 
						FROM 
							".$wbasedato."_000017, ".$wbasedato."_000011 
						WHERE 
							Eyrhis = '$paciente->historiaClinica' 
							AND Eyring = '$paciente->ingresoHistoriaClinica' 
							AND Eyrtip = 'Recibo' 
							AND ".$wbasedato."_000017.Fecha_data = '".$wfecha."' 
							AND Ccocod = Eyrsor	
							AND Eyrest='on';";
	
				if($contMv>0){
					$infoMv = mysql_fetch_array($resMv);
					//Si hubo traslado del dia, se verifica que sea de urgencias o cirugia
					if(isset($infoMv['Ccourg']) && isset($infoMv['Ccocir']) && isset($infoMv['Ccoing']) && ($infoMv['Ccourg'] == "on" || $infoMv['Ccocir'] == "on" || $infoMv['Ccoing'] == "on")){
						//Si el kardex no fue creado en la misma fecha del traslado, no acumula saldo
						if($kardex->fechaCreacion != $wfecha){
							$kardex->noAcumulaSaldoDispensacion = false;
						} else { //Si el kardex fue creado en la misma fecha del traslado, si acumula saldo
							$kardex->noAcumulaSaldoDispensacion = true;
						}
					} //El paciente en el traslado anterior no estuvo en urgencias o cirugia, necesariamente, debe estar en un serv. hospitalario
					$kardex->horaDescuentoDispensaciones = $infoMv['hora_data'];
				}
			}
		}
		return $kardex;
	}

}
/*
class kardexDTO {
	var $historia = "";
	var $ingreso = "";
	var $fechaCreacion = "";
	var $fechaGrabacion = "";
	var $horaCreacion = "";
	var $observaciones = "";
	var $estado = "";
	var $rutaOrdenMedica = "";
	var $diagnostico = "";
	var $talla = "";
	var $peso = "";
	var $antecedentesAlergicos = "";
	var $cuidadosEnfermeria = "";
	var $terapiaRespiratoria = "";
	var $sondasCateteres = "";
	var $curaciones = "";
	var $interconsulta = "";
	var $consentimientos = "";
	var $medidasGenerales = "";
	var $preparacionAlta = "";
	var $confirmado = "";
	var $usuario = "";

	var $firmaDigital = "";
	var $obsDietas = "";
	var $mezclas = "";
	var $procedimientos = "";
	var $dextrometer = "";
	var $cirugiasPendientes = "";
	var $terapiaFisica = "";
	var $rehabilitacionCardiaca = "";
	var $antecedentesPersonales = "";
	var $aislamientos = "";

	var $esAnterior = "";
	var $editable = "";
	var $grabado = "";
	var $aprobado = "";
	var $esPrimerKardex = "";

	var $usuarioQueModifica = "";
	var $nombreUsuarioQueModifica = "";
	var $centroCostos = "";
	var $noAcumulaSaldoDispensacion = "";
	var $descontarDispensaciones = "";
	var $horaDescuentoDispensaciones = "";
}
*/
?>