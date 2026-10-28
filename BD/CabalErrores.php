<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Diccionarios Oficiales de Códigos de Error y Respuesta Cabal
 * Especificación Técnica: docs/Cabal.md (Anexo 11, Anexo 12 y Anexo 13)
 * Compatibilidad: PHP 5.6.40+
 */

/**
 * Retorna el diccionario completo de errores para Altas y Modificaciones (Anexo 12)
 *
 * @return array
 */
function obtenerDiccionarioErroresAltasCabal()
{
    static $diccionarioAltas = null;
    if ($diccionarioAltas === null) {
        $diccionarioAltas = array(
            '001' => array(
                'codigo' => '001',
                'titulo' => 'Identificación no válida',
                'desc'   => 'El tipo o número de identificación no cumple con los criterios de validación de Cabal.'
            ),
            '002' => array(
                'codigo' => '002',
                'titulo' => 'Código de novedad inexistente',
                'desc'   => 'El código de operación de alta informado no existe en los catálogos del sistema.'
            ),
            '003' => array(
                'codigo' => '003',
                'titulo' => 'Novedad repetida en proceso en el mismo lote',
                'desc'   => 'Se detectó más de una novedad para el mismo titular dentro de la misma corrida/lote.'
            ),
            '004' => array(
                'codigo' => '004',
                'titulo' => 'Estructura de domicilio errónea o fuera de formato',
                'desc'   => 'Calle, número, piso o departamento excede longitudes permitidas o tiene caracteres prohibidos.'
            ),
            '005' => array(
                'codigo' => '005',
                'titulo' => 'Falta informar campo obligatorio: Localidad',
                'desc'   => 'El campo Localidad fue recibido en blanco o con caracteres inválidos.'
            ),
            '006' => array(
                'codigo' => '006',
                'titulo' => 'Código de provincia no válido (Revisar Anexo 9)',
                'desc'   => 'El código numérico de provincia no coincide con la tabla oficial de provincias de Cabal.'
            ),
            '007' => array(
                'codigo' => '007',
                'titulo' => 'Código Postal no numérico o en blanco',
                'desc'   => 'El código postal contiene caracteres inválidos o carece de valor numérico válido.'
            ),
            '008' => array(
                'codigo' => '008',
                'titulo' => 'Código de país no numérico',
                'desc'   => 'El código de país debe ser numérico según la especificación Cabal (generalmente 000).'
            ),
            '009' => array(
                'codigo' => '009',
                'titulo' => 'Teléfono no numérico',
                'desc'   => 'La característica o el número telefónico contiene letras, guiones o espacios no permitidos.'
            ),
            '010' => array(
                'codigo' => '010',
                'titulo' => 'Código de sexo erróneo o fuera de parámetro',
                'desc'   => 'El valor de sexo no corresponde a los valores admitidos (M o F).'
            ),
            '011' => array(
                'codigo' => '011',
                'titulo' => 'Estado Civil erróneo o código no reconocido',
                'desc'   => 'El código de estado civil difiere de los valores homologados (C, S, V, O).'
            ),
            '012' => array(
                'codigo' => '012',
                'titulo' => 'Fecha de nacimiento ilógica o inconsistente',
                'desc'   => 'La fecha de nacimiento no es una fecha válida en el calendario o tiene formato incorrecto.'
            ),
            '013' => array(
                'codigo' => '013',
                'titulo' => 'Titular con menor edad de la permitida por el plan',
                'desc'   => 'La edad del titular calculada a partir de su fecha de nacimiento es menor al mínimo reglamentario.'
            ),
            '014' => array(
                'codigo' => '014',
                'titulo' => 'Profesión inválida o no nomenclada',
                'desc'   => 'El código de ocupación o profesión no se encuentra en las tablas de afinidad de Cabal.'
            ),
            '021' => array(
                'codigo' => '021',
                'titulo' => 'Bloqueo causa 05 para tarjeta con causa 07',
                'desc'   => 'Inconsistencia en el estado operativo de la tarjeta en los maestros del Host.'
            ),
            '027' => array(
                'codigo' => '027',
                'titulo' => 'Causa tarjeta no existe en la parametrización',
                'desc'   => 'El motivo de asignación o causa no está definido en los sistemas centrales.'
            ),
            '028' => array(
                'codigo' => '028',
                'titulo' => 'Solo las siguientes causas están habilitadas: 02, 03, 14',
                'desc'   => 'Se utilizó una causa de operación no permitida para esta entidad o producto.'
            ),
            '031' => array(
                'codigo' => '031',
                'titulo' => 'Número de Formulario inválido o corrupto',
                'desc'   => 'El secuencial de formulario difiere entre los registros 01, 02 y 03 del mismo titular.'
            ),
            '032' => array(
                'codigo' => '032',
                'titulo' => 'La edad del titular supera la máxima definida',
                'desc'   => 'La edad del beneficiario excede el límite máximo parametrizado para el programa social.'
            ),
            '033' => array(
                'codigo' => '033',
                'titulo' => 'Grupo afinidad no válido',
                'desc'   => 'El código de grupo o subprograma social no es reconocido por la entidad 782.'
            ),
            '034' => array(
                'codigo' => '034',
                'titulo' => 'No existe grupo de afinidad en los maestros',
                'desc'   => 'El grupo de afinidad no fue dado de alta previamente en Cabal Casa Central.'
            ),
            '036' => array(
                'codigo' => '036',
                'titulo' => 'Número de CUIT/CUIL no numérico o inválido / Titular inactivo',
                'desc'   => 'El CUIT/CUIL contiene formato erróneo, dígito verificador inválido o titular inactivo para adicional.'
            ),
            '037' => array(
                'codigo' => '037',
                'titulo' => 'Grupo afinidad dado de baja o no habilitado',
                'desc'   => 'El grupo de afinidad asignado ha caducado o se encuentra suspendido.'
            ),
            '041' => array(
                'codigo' => '041',
                'titulo' => 'Señal de retención mal formada en la trama',
                'desc'   => 'Error de sintaxis en las marcas de control de emisión en filial.'
            ),
            '060' => array(
                'codigo' => '060',
                'titulo' => 'Campo Nombre obligatorio en blanco',
                'desc'   => 'El nombre del beneficiario no fue completado tras el separador de barra.'
            ),
            '061' => array(
                'codigo' => '061',
                'titulo' => 'Señal de reemisión no válida',
                'desc'   => 'El indicador de reposición o reimpresión de plástico es incorrecto.'
            ),
            '065' => array(
                'codigo' => '065',
                'titulo' => 'Error intrínseco en campo Documento 1',
                'desc'   => 'El número de documento de identidad posee caracteres no numéricos o longitud errónea.'
            ),
            '066' => array(
                'codigo' => '066',
                'titulo' => 'Error en Tipo de Documento 1 (Revisar Anexo 10)',
                'desc'   => 'El tipo de documento informado no existe en el catálogo Cabal (DNI debe ser 03).'
            ),
            '080' => array(
                'codigo' => '080',
                'titulo' => 'Código de modificación no válido en la posición 23',
                'desc'   => 'Operación no reconocida para el registro filiatorio.'
            ),
            '081' => array(
                'codigo' => '081',
                'titulo' => 'Modificación para tarjeta inhabilitada o dada de baja',
                'desc'   => 'No se pueden alterar los datos filiatorios de una tarjeta cancelada.'
            ),
            '088' => array(
                'codigo' => '088',
                'titulo' => 'Banco emisor nuevo distinto del anterior registrado',
                'desc'   => 'Conflicto de entidad bancaria emisora en el clearing central.'
            ),
            '092' => array(
                'codigo' => '092',
                'titulo' => 'Reactivación permitida exclusivamente para causa 08',
                'desc'   => 'Intento de desbloqueo de plástico no admitido para el estado actual.'
            ),
            '115' => array(
                'codigo' => '115',
                'titulo' => 'Mensaje de cuarta línea mal formado (Excede longitud)',
                'desc'   => 'La leyenda de impresión para el plástico físico supera los 25 caracteres reglamentarios.'
            ),
            '301' => array(
                'codigo' => '301',
                'titulo' => 'Banco/Sucursal inexistente en clearing central',
                'desc'   => 'La combinación Entidad 782 y Sucursal no está habilitada en el host central.'
            ),
            '305' => array(
                'codigo' => '305',
                'titulo' => 'Nro de cuenta no numérico o mal dígito verificador',
                'desc'   => 'El algoritmo de chequeo de la cuenta asignada falló en el host central.'
            ),
            '306' => array(
                'codigo' => '306',
                'titulo' => 'Tarjeta inexistente en los maestros históricos',
                'desc'   => 'El número de tarjeta no corresponde a los lotes de plásticos asignados a la Entidad 782.'
            ),
            '999' => array(
                'codigo' => '999',
                'titulo' => 'Error Interno al procesar: Comuníquese con Sistemas',
                'desc'   => 'Falla no recuperable en el mainframe de Cabal durante la corrida batch.'
            )
        );
    }
    return $diccionarioAltas;
}

/**
 * Traduce un código individual de error de altas según Anexo 12
 *
 * @param string $codigo Código de 3 dígitos (ej. "006")
 * @return array
 */
function interpretarCodigoErrorAltaCabal($codigo)
{
    $codigoRaw = trim($codigo);
    $dict = obtenerDiccionarioErroresAltasCabal();

    // 1. Coincidencia directa de 3 dígitos
    $codigoLimpio = preg_replace('/^R/i', '', $codigoRaw);
    if (is_numeric($codigoLimpio)) {
        $codigoNorm = str_pad($codigoLimpio, 3, '0', STR_PAD_LEFT);
        if (isset($dict[$codigoNorm])) {
            return array(
                'codigo' => $codigoNorm,
                'titulo' => $dict[$codigoNorm]['titulo'],
                'desc'   => $dict[$codigoNorm]['desc'],
                'tipo'   => 'danger'
            );
        }
    }

    // 2. Coincidencia compuesta estilo "R05-DATOS INCOMPLETOS" o "005 - Localidad"
    if (preg_match('/^R?(\d+)[-\s:]*(.*)$/i', $codigoRaw, $m)) {
        $numPart = str_pad($m[1], 3, '0', STR_PAD_LEFT);
        if (isset($dict[$numPart])) {
            $extra = trim($m[2]);
            return array(
                'codigo' => $numPart,
                'titulo' => $dict[$numPart]['titulo'] . ($extra !== '' ? ' (' . $extra . ')' : ''),
                'desc'   => $dict[$numPart]['desc'],
                'tipo'   => 'danger'
            );
        }
    }

    return array(
        'codigo' => $codigoRaw,
        'titulo' => 'Rechazo Cabal: ' . $codigoRaw,
        'desc'   => 'Código de rechazo retornado por el host de Cabal (ver Anexo 12 en docs/Cabal.md).',
        'tipo'   => 'danger'
    );
}

/**
 * Parsea el campo de códigos de rechazo de 36 bytes (pos 130-165) de la respuesta de Altas (Anexo 2)
 * Se pueden informar hasta 9 códigos de 3 dígitos seguidos por un espacio.
 *
 * @param string $stringRechazo
 * @return array Lista de arrays interpretados con ['codigo', 'titulo', 'desc', 'tipo']
 */
function parsearCodigosRechazoAltas($stringRechazo)
{
    $stringLimpio = trim($stringRechazo);
    if ($stringLimpio === '' || preg_replace('/[0\s]/', '', $stringLimpio) === '') {
        return array();
    }

    $codigosExtraidos = array();

    // Si viene en formato textual continuo compuesto (ej. "R05-DATOS INCOMPLETOS")
    if (preg_match('/^R\d+[-_]/i', $stringLimpio)) {
        $codigosExtraidos[] = $stringLimpio;
    } else {
        $tokens = preg_split('/\s+/', $stringLimpio);
        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '' || $token === '0' || $token === '00' || $token === '000') {
                continue;
            }

            if (strlen($token) === 3 || preg_match('/^R\d{1,2}$/i', $token)) {
                $codigosExtraidos[] = $token;
            } elseif (strlen($token) > 3 && is_numeric($token)) {
                // Múltiples códigos concatenados sin espacios
                $partes = str_split($token, 3);
                foreach ($partes as $p) {
                    if ($p !== '' && $p !== '000') {
                        $codigosExtraidos[] = $p;
                    }
                }
            } else {
                $codigosExtraidos[] = $token;
            }
        }
    }

    if (empty($codigosExtraidos) && !empty($stringLimpio)) {
        $codigosExtraidos[] = $stringLimpio;
    }

    $resultado = array();
    foreach (array_unique($codigosExtraidos) as $cod) {
        $resultado[] = interpretarCodigoErrorAltaCabal($cod);
    }

    return $resultado;
}

/**
 * Retorna el diccionario completo de errores en operaciones de Carga y Descarga (Anexo 11)
 *
 * @return array
 */
function obtenerDiccionarioErroresCargasCabal()
{
    static $diccionarioCargas = null;
    if ($diccionarioCargas === null) {
        $diccionarioCargas = array(
            // OPERACIÓN EXITOSA
            '000' => array(
                'codigo' => '000',
                'segmento' => 'DETALLE',
                'titulo' => 'Acreditación Exitosa / Aprobada',
                'desc'   => 'Crédito aplicado correctamente en la cuenta del beneficiario.',
                'tipo'   => 'success'
            ),
            // HEADER
            'H001' => array(
                'codigo' => '001',
                'segmento' => 'HEADER',
                'titulo' => 'Entidad Inexistente o Falta Trailer',
                'desc'   => 'Falta el bloque de cierre técnico al final del archivo o el código de banco está fuera de los maestros.',
                'tipo'   => 'danger'
            ),
            'H002' => array(
                'codigo' => '002',
                'segmento' => 'HEADER',
                'titulo' => 'Nro. de Tarjeta diferente de ceros',
                'desc'   => 'Error de formato en cabecera: las posiciones de cuenta deben ser estrictamente 0.',
                'tipo'   => 'danger'
            ),
            'H003' => array(
                'codigo' => '003',
                'segmento' => 'HEADER',
                'titulo' => 'Fecha de proceso inválida',
                'desc'   => 'Formato de fecha no numérico o fuera del calendario real.',
                'tipo'   => 'danger'
            ),
            'H004' => array(
                'codigo' => '004',
                'segmento' => 'HEADER',
                'titulo' => 'Hora de proceso no numérica',
                'desc'   => 'Formato de hora mal estructurado en la cabecera.',
                'tipo'   => 'danger'
            ),
            'H005' => array(
                'codigo' => '005',
                'segmento' => 'HEADER',
                'titulo' => 'Código de operación inválido',
                'desc'   => 'El código del Header no coincide con ninguna operación válida (80, 62, 63, 90).',
                'tipo'   => 'danger'
            ),
            'H006' => array(
                'codigo' => '006',
                'segmento' => 'HEADER',
                'titulo' => 'Rechazo por tesorería',
                'desc'   => 'El departamento de control financiero bloqueó manualmente la aplicación del lote.',
                'tipo'   => 'danger'
            ),
            // DETALLE
            '001' => array(
                'codigo' => '001',
                'segmento' => 'DETALLE',
                'titulo' => 'Falta Header y Trailer',
                'desc'   => 'El archivo de texto carece de las estructuras de control iniciales o de cierre.',
                'tipo'   => 'danger'
            ),
            '002' => array(
                'codigo' => '002',
                'segmento' => 'DETALLE',
                'titulo' => 'Tarjeta no numérica o inexistente',
                'desc'   => 'El número de cuenta ingresado en la línea no existe en la base histórica de Cabal.',
                'tipo'   => 'danger'
            ),
            '003' => array(
                'codigo' => '003',
                'segmento' => 'DETALLE',
                'titulo' => 'Código de operación diferente de 62/63',
                'desc'   => 'La línea posee una operación prohibida para este canal.',
                'tipo'   => 'danger'
            ),
            '004' => array(
                'codigo' => '004',
                'segmento' => 'DETALLE',
                'titulo' => 'Importe de Compra erróneo',
                'desc'   => 'El formato del número decimal de compras posee caracteres alfabéticos o longitud inválida.',
                'tipo'   => 'danger'
            ),
            '005' => array(
                'codigo' => '005',
                'segmento' => 'DETALLE',
                'titulo' => 'Importe de Retiros erróneo',
                'desc'   => 'El formato del número decimal de extracción en cajeros es inválido.',
                'tipo'   => 'danger'
            ),
            '006' => array(
                'codigo' => '006',
                'segmento' => 'DETALLE',
                'titulo' => 'Desalineación de Operación',
                'desc'   => 'El código de operación de la línea individual difiere del tipo de archivo declarado en el Header.',
                'tipo'   => 'warning'
            ),
            '007' => array(
                'codigo' => '007',
                'segmento' => 'DETALLE',
                'titulo' => 'Subcódigo de operación erróneo',
                'desc'   => 'No se utilizó la constante 80 para transferencias o 39 para cargas/descargas ordinarias.',
                'tipo'   => 'warning'
            ),
            '008' => array(
                'codigo' => '008',
                'segmento' => 'DETALLE',
                'titulo' => 'Inconsistencia de Estado (Bloqueo)',
                'desc'   => 'La tarjeta se encuentra en un estado inactivo o bloqueado que impide procesar la carga o descarga.',
                'tipo'   => 'danger'
            ),
            '009' => array(
                'codigo' => '009',
                'segmento' => 'DETALLE',
                'titulo' => 'Importe mayor al disponible',
                'desc'   => 'Rechazo por saldo insuficiente: la cuenta no posee fondos líquidos para cubrir la descarga o transferencia.',
                'tipo'   => 'danger'
            ),
            '010' => array(
                'codigo' => '010',
                'segmento' => 'DETALLE',
                'titulo' => 'Motivo de carga mal informado',
                'desc'   => 'Estructura incorrecta en los parámetros de justificación de fondos.',
                'tipo'   => 'danger'
            ),
            '011' => array(
                'codigo' => '011',
                'segmento' => 'DETALLE',
                'titulo' => 'Número de carga mal informado',
                'desc'   => 'El secuencial provisto por la entidad está duplicado o mal estructurado.',
                'tipo'   => 'danger'
            ),
            '012' => array(
                'codigo' => '012',
                'segmento' => 'DETALLE',
                'titulo' => 'Consulte a Cabal',
                'desc'   => 'Error genérico crítico de base de datos en el Host central.',
                'tipo'   => 'danger'
            ),
            '017' => array(
                'codigo' => '017',
                'segmento' => 'DETALLE',
                'titulo' => 'Entidades diferentes',
                'desc'   => 'Conflicto de clearing: el código de banco de la línea no coincide con los registros de control.',
                'tipo'   => 'warning'
            ),
            '099' => array(
                'codigo' => '099',
                'segmento' => 'DETALLE',
                'titulo' => 'Rechazo General Cabal',
                'desc'   => 'Rechazo operativo general no clasificado por la entidad.',
                'tipo'   => 'danger'
            ),
            // TRAILER
            'T002' => array(
                'codigo' => '002',
                'segmento' => 'TRAILER',
                'titulo' => 'Nro. Tarjeta diferente de nueves',
                'desc'   => 'Error de formato: el campo de control de cierre debe contener estrictamente el valor 9999999999999999.',
                'tipo'   => 'danger'
            ),
            'T003' => array(
                'codigo' => '003',
                'segmento' => 'TRAILER',
                'titulo' => 'Descalce en cantidad de registros',
                'desc'   => 'El contador físico de líneas del proceso arrojó un número distinto al declarado en el Trailer.',
                'tipo'   => 'danger'
            ),
            'T004' => array(
                'codigo' => '004',
                'segmento' => 'TRAILER',
                'titulo' => 'Descalce en sumatoria de Compras',
                'desc'   => 'La suma aritmética de los campos de importe difiere del totalizador de control enviado.',
                'tipo'   => 'danger'
            ),
            'T005' => array(
                'codigo' => '005',
                'segmento' => 'TRAILER',
                'titulo' => 'Descalce en sumatoria de Cajeros',
                'desc'   => 'La suma aritmética de los campos de cajeros difiere del totalizador de control enviado.',
                'tipo'   => 'danger'
            ),
            'T006' => array(
                'codigo' => '006',
                'segmento' => 'TRAILER',
                'titulo' => 'Importe carga <> descarga',
                'desc'   => 'Rompe la regla de Suma Cero en el archivo consolidado de transferencias inter-tarjetas.',
                'tipo'   => 'danger'
            ),
            'T008' => array(
                'codigo' => '008',
                'segmento' => 'TRAILER',
                'titulo' => 'Carga Duplicada',
                'desc'   => 'El identificador único del lote ya fue inyectado y procesado en el sistema central anteriormente.',
                'tipo'   => 'danger'
            ),
            'T009' => array(
                'codigo' => '009',
                'segmento' => 'TRAILER',
                'titulo' => 'Error en descarga previa',
                'desc'   => 'Falló el proceso algorítmico automatizado de limpieza de saldos remanentes (Código 90).',
                'tipo'   => 'danger'
            )
        );
    }
    return $diccionarioCargas;
}

/**
 * Traduce un código individual de respuesta en operaciones de carga/descarga (Anexo 11)
 *
 * @param string $codigo
 * @param string $segmento 'HEADER', 'DETALLE' o 'TRAILER'
 * @return array
 */
function interpretarCodigoErrorCargaCabal($codigo, $segmento = 'DETALLE')
{
    $codigoNorm = str_pad(trim($codigo), 3, '0', STR_PAD_LEFT);
    $dict = obtenerDiccionarioErroresCargasCabal();

    // Prefijo según segmento si no es detalle
    $claveSegmento = '';
    if ($segmento === 'HEADER') {
        $claveSegmento = 'H' . $codigoNorm;
    } elseif ($segmento === 'TRAILER') {
        $claveSegmento = 'T' . $codigoNorm;
    }

    if ($claveSegmento !== '' && isset($dict[$claveSegmento])) {
        return $dict[$claveSegmento];
    }

    if (isset($dict[$codigoNorm])) {
        return $dict[$codigoNorm];
    }

    if ($codigoNorm === '000') {
        return $dict['000'];
    }

    return array(
        'codigo'   => $codigoNorm,
        'segmento' => $segmento,
        'titulo'   => 'Error Cabal Código ' . $codigoNorm,
        'desc'     => 'Código de rechazo retornado por la plataforma Cabal (ver Anexo 11 en docs/Cabal.md).',
        'tipo'     => 'danger'
    );
}

/**
 * Retorna la matriz de causas y estados de bloqueo de tarjetas (Anexo 13)
 *
 * @return array
 */
function obtenerDiccionarioCausasBloqueoCabal()
{
    static $diccionarioBloqueo = null;
    if ($diccionarioBloqueo === null) {
        $diccionarioBloqueo = array(
            '00' => array(
                'codigo' => '00',
                'denominacion' => 'HABILITADA / ACTIVA',
                'regla' => 'Tarjeta operativa habilitada para compras y extracciones.',
                'tipo'  => 'success'
            ),
            '01' => array(
                'codigo' => '01',
                'denominacion' => 'DENUNCIA POR EXTRAVÍO',
                'regla' => 'Estado irreversible de seguridad. Bloquea de forma inmediata el clearing del plástico.',
                'tipo'  => 'danger'
            ),
            '02' => array(
                'codigo' => '02',
                'denominacion' => 'ANULACIÓN POR ENTIDAD',
                'regla' => 'Estado administrativo definitivo. Ordenado por el ente gestor para dar de baja el beneficio.',
                'tipo'  => 'danger'
            ),
            '03' => array(
                'codigo' => '03',
                'denominacion' => 'ANULACIÓN',
                'regla' => 'Baja general del registro de la cuenta en los sistemas centrales.',
                'tipo'  => 'danger'
            ),
            '04' => array(
                'codigo' => '04',
                'denominacion' => 'DENUNCIA POR HURTO',
                'regla' => 'Bloqueo irreversible de seguridad ante robo. Deniega compras automáticas.',
                'tipo'  => 'danger'
            ),
            '07' => array(
                'codigo' => '07',
                'denominacion' => 'REEMPLAZO',
                'regla' => 'Estado intermedio. El plástico físico se cancela y se ordena la impresión de una nueva versión.',
                'tipo'  => 'warning'
            ),
            '08' => array(
                'codigo' => '08',
                'denominacion' => 'NO HABILITADA',
                'regla' => 'Estado nativo inicial (Preemisión). La tarjeta está impresa pero bloqueada para su uso.',
                'tipo'  => 'warning'
            ),
            '14' => array(
                'codigo' => '14',
                'denominacion' => 'BLOQUEO TRANSITORIO',
                'regla' => 'Restricción Exclusiva de Transición: Suprime reversión a Causa 08. Solo se valida paso de 00 hacia 14.',
                'tipo'  => 'warning'
            )
        );
    }
    return $diccionarioBloqueo;
}

/**
 * Traduce un código de causa o estado de plástico (Anexo 13)
 *
 * @param string $codigo
 * @return array
 */
function interpretarCausaBloqueoCabal($codigo)
{
    $codigoNorm = str_pad(trim($codigo), 2, '0', STR_PAD_LEFT);
    $dict = obtenerDiccionarioCausasBloqueoCabal();

    if (isset($dict[$codigoNorm])) {
        return $dict[$codigoNorm];
    }

    return array(
        'codigo'       => $codigoNorm,
        'denominacion' => 'ESTADO NO ESPECIFICADO (' . $codigoNorm . ')',
        'regla'        => 'Estado de plástico no nomenclado en Anexo 13 de Cabal.',
        'tipo'         => 'neutral'
    );
}
