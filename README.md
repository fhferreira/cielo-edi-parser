# EdiParser
Parser para o arquivo do tipo EDI, transmitido pela Cielo para conciliação bancária

> Fork criado para ajuste de conciliação de cartões de Crédito CIELO

[![Build Status](https://secure.travis-ci.org/Pandora-una/EdiParser.png?branch=master)](http://travis-ci.org/Pandora-una/EdiParser)
[![Latest Stable Version](https://poser.pugx.org/pandora-una/edi-parser/v/stable)](https://packagist.org/packages/pandora-una/edi-parser) 
[![Latest Unstable Version](https://poser.pugx.org/pandora-una/edi-parser/v/unstable)](https://packagist.org/packages/pandora-una/edi-parser) 
[![Total Downloads](https://poser.pugx.org/pandora-una/edi-parser/downloads)](https://packagist.org/packages/pandora-una/edi-parser) 
[![Code Climate](https://codeclimate.com/github/Pandora-una/EdiParser/badges/gpa.svg)](https://codeclimate.com/github/Pandora-una/EdiParser)
[![Test Coverage](https://codeclimate.com/github/Pandora-una/EdiParser/badges/coverage.svg)](https://codeclimate.com/github/Pandora-una/EdiParser/coverage)


```php
<?php

use EdiParser\Arquivo\Arquivo;

function calculaComissao($value, $percent)
{
    return ($value / 100) * $percent;
}

function formatNumber($value)
{
    return number_format($value, 2, ',', '');
}

function baixaParcela($db, $cartao)
{
    try {

        $num_parcela = intval($cartao['parcela']);
        if ($num_parcela == 0) {
            $num_parcela = 1;
        }

        $parcelaDesc = "valor_" . $num_parcela . "_parcela";
        $parcelaRecebidoDesc = 'valor_' . $num_parcela . '_recebido';
        $parcelaStatusDesc = 'status_recebido_' . $num_parcela . '_parcela';
        $parcelaCustoDesc = 'custo_recebimento_' . $num_parcela . '_parcela';
        $parcelaDataDesc = 'data_recebimento_' . $num_parcela . '_parcela_operadora';
        $parcelaDataBaixaDesc = 'data_baixa_automatica_' . $num_parcela . '_parcela';
        $parcelaUsuarioBaixaDesc = 'usuario_baixa_automatica_' . $num_parcela . '_parcela';

        $nr_usuario_id = 1;
        $liquido = round($cartao["valorLiquido"],2, PHP_ROUND_HALF_DOWN);
        $taxa = round($cartao["valorComissao"] ,2);

        $updateSQL = "update cartao_de_credito
                        set
                        {$parcelaRecebidoDesc}  = '" . number_format($liquido, 2, '.', '') . "',
                        {$parcelaStatusDesc} = 'S',
                        {$parcelaCustoDesc}  = '" . number_format($taxa, 2, '.', '') . "',
                        {$parcelaDataDesc}    = '" . $cartao["dtBaixa"]->format("Y-m-d") . "',
                        {$parcelaDataBaixaDesc} = '" . date("Y-m-d H:i:s") . "',
                        {$parcelaUsuarioBaixaDesc} = '" . $nr_usuario_id . "'
                        where {$parcelaStatusDesc} <> 'S' AND  id_cad_cartao = " . $cartao['id_cad_cartao'];

        $p_sql = $db->prepare($updateSQL);
        $p_sql->execute();

        $sqlUpdateSaldo = "UPDATE cartao_de_credito
                        SET saldo_a_receber = (valor_da_conta - ROUND((
                          (valor_1_recebido + custo_recebimento_1_parcela)
                        + (valor_2_recebido + custo_recebimento_2_parcela)
                        + (valor_3_recebido + custo_recebimento_3_parcela)
                        + (valor_4_recebido + custo_recebimento_4_parcela)
                        + (valor_5_recebido + custo_recebimento_5_parcela)
                        + (valor_6_recebido + custo_recebimento_6_parcela)
                        ),2)) WHERE  id_cad_cartao = " . $cartao['id_cad_cartao'];

        $p_sql = $db->prepare($sqlUpdateSaldo);
        $p_sql->execute();

        return true;

    } catch (\Exception $e) {
        var_dump($e, $cartao);
    }

    return false;
}

try{

$valorGeral = 0;
$valorSemComprovantesGeral = 0;
$valoresComissao = array();
$valorGeralLiquido = 0;
$valoresCancelados = array();
$countSemComprovantesGeral = 0;

$valoresCobrancas = array();
$resumos = array();
$valorPorComprovanteNaoEncontrados = array();

$DB_HOST = env('DB_HOST');
$DB_DATABASE = env('DB_DATABASE');
$DB_USERNAME = env('DB_USERNAME');
$DB_PASSWORD = env('DB_PASSWORD');

$db = new PDO("mysql:host={$DB_HOST};port=3306;dbname={$DB_DATABASE}", 
$DB_USERNAME, 
$DB_PASSWORD, 
array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);

echo "<div class='container'>";
echo "<div class='row'>";
echo "<div class='col-md-12'>";

foreach ($arquivos as $filename) {
    try {
        $arquivo = new Arquivo($filename);
        $detalhes = $arquivo->getDetalhesRO();
        $cont = 1;

        $valorBruto = array();
        $detalhesCount = array();
        $detalhesValor = array();
        $valorPorComprovante = array();
        $valorSemComprovante = array();
        $countSemCVs = 0;
        $sumSemCVs = array();

        $valorLiquido = array();
        $valorComissao = array();
        $valorCobrancas = array();
        $jaBaixados = array();

        $count = 0;

        foreach ($detalhes as $detalhe) {
            $valorBruto[] = $detalhe->getValorBruto();
            $detalhesCV = $detalhe->getDetalhesCV();

            $detalhesCount[] = count($detalhesCV);

            $bruto = $detalhe->getValorBruto();

            $valorPorComprovante[$cont]['total'] = $bruto;

            if ($bruto < 0 && count($detalhesCV) == 0) {
                $valorCobrancas[] = $detalhe->getValorBruto();
            } elseif ($bruto < 0 && count($detalhesCV) > 0) {
                $valoresCancelados[] = $detalhe->getValorBruto();
            }

            $valorLiquido[] = $valorPorComprovante[$cont]['liquido'] = $detalhe->getValorLiquido();
            $valorPorComprovante[$cont]['dtPrevPagamento'] = $detalhe->getDtPrevPagamento();
            $valorPorComprovante[$cont]['quantidadeCVs'] = $detalhe->getQuantidadeCVs();

            $valorComissaoResumo = $detalhe->getValorComissao();
            $valorComissao[] = $valorComissaoResumo;
            $valorPorComprovante[$cont]['valorComissao'] = $valorComissaoResumo;

            $taxaComissao = $detalhe->getTaxaDeComissao();
            $valorPorComprovante[$cont]['taxaDeComissao'] = $taxaComissao;

            $valorPorComprovante[$cont]['tarifa'] = $detalhe->getTarifa();
            $valorPorComprovante[$cont]['IdentificadorProduto'] = $detalhe->getIdentificadorProduto();
            $valorPorComprovante[$cont]['IdentificadorProdutoDesc'] = $detalhe->getIdentificadorProdutoDesc();
            $valorPorComprovante[$cont]['numeroRO'] = $detalhe->getNumeroRO();
            $valorPorComprovante[$cont]['codigoBandeira'] = $detalhe->getCodigoBandeira();
            $dtCaptura = $detalhe->getDtCaptura();
            $valorPorComprovante[$cont]['dtCaptura'] = $dtCaptura;
            $valorPorComprovante[$cont]['Bandeira'] = $detalhe->getBandeira($valorPorComprovante[$cont]['codigoBandeira']);
            $valorPorComprovante[$cont]['valores'] = array();

            if (count($detalhesCV)) {

                foreach ($detalhesCV as $detalheCV) {

                    $nsuDoc = $detalheCV->getNsuDoc();
                    $codigoAutorizacao = $detalheCV->getCodigoAutorizacao();
                    $valor = $detalheCV->getValor();
                    $parcela = $detalheCV->getParcela();
                    $totalParcelas = $detalheCV->getTotalParcelas();

                    $num_parcela = intval($parcela);
                    if ($num_parcela == 0) {
                        $num_parcela = 1;
                    }

                    $parcelaDesc = "valor_" . $num_parcela . "_parcela";
                    $parcelaRecebidoDesc = 'valor_' . $num_parcela . '_recebido';
                    $parcelaStatusDesc = 'status_recebido_' . $num_parcela . '_parcela';
                    $parcelaCustoDesc = 'custo_recebimento_' . $num_parcela . '_parcela';
                    $parcelaDataDesc = 'data_recebimento_' . $num_parcela . '_parcela_operadora';
                    $parcelaDataBaixaDesc = 'data_baixa_automatica_' . $num_parcela . '_parcela';
                    $parcelaUsuarioBaixaDesc = 'usuario_baixa_automatica_' . $num_parcela . '_parcela';

                    try {

                        $sql = "SELECT
                                c.id_cad_cartao,
                                c.data_da_conta,
                                c.operadora_do_cartao,
                                c.debito_ou_credito,
                                c.quantidade_de_parcelas,
                                c.titular_do_cartao,
                                c.valor_da_conta,
                                c.origem_da_conta,
                                c.contrato,
                                c.{$parcelaDesc},
                                c.{$parcelaRecebidoDesc},
                                c.{$parcelaStatusDesc},
                                c.{$parcelaCustoDesc},
                                c.{$parcelaDataDesc},
                                c.{$parcelaDataBaixaDesc},
                                c.{$parcelaUsuarioBaixaDesc},
                                IF(c.{$parcelaRecebidoDesc} > 0, 1, 0) as StatusParcela
                        FROM
                            cartao_de_credito c
                        WHERE
                            c.documento = :nsuDoc
                        AND c.autorizacao = :codigoAutorizacao
                        AND CAST(c.{$parcelaDesc} AS DECIMAL) = CAST(:valor AS DECIMAL)";

                        $p_sql = $db->prepare($sql);
                        $p_sql->bindValue(":nsuDoc", $nsuDoc);
                        $p_sql->bindValue(":codigoAutorizacao", $codigoAutorizacao);
                        $p_sql->bindValue(":valor", $valor);
                        $p_sql->execute();
                        $numRows = $p_sql->rowCount();
                        $cartao = $p_sql->fetch(PDO::FETCH_ASSOC);

                        $localizado = false;
                        $mensagem = '';

                        if ($numRows) {
                            $localizado = true;
                        } else {

                            $cartao = array();

                            echo "<pre>";
                            echo "<h2 style='color:red;'>Cartão não encontrado !!</h2>";
                            echo "<h4>nsuDoc: {$nsuDoc}</h4>";
                            echo "<h4>codigoAutorizacao: {$codigoAutorizacao}</h4>";
                            echo "<h4>Valor: {$valor}</h4>";
                            echo "<h4>Data Previsão: {$valorPorComprovante[$cont]['dtPrevPagamento']->format('d/m/Y')}</h4>";
                            echo "<h4>Bandeira: {$valorPorComprovante[$cont]['Bandeira']}</h4>";
                            echo "<h4>Produto: {$valorPorComprovante[$cont]['IdentificadorProdutoDesc']}</h4>";
                            echo "<h4>Dt. Captura: {$valorPorComprovante[$cont]['dtCaptura']->format('d/m/Y')}</h4>";

                            $p_sql = $db->prepare($sql);
                            $p_sql->bindValue(":nsuDoc", $codigoAutorizacao);
                            $p_sql->bindValue(":codigoAutorizacao", $nsuDoc);
                            $p_sql->bindValue(":valor", $valor);
                            $p_sql->execute();
                            $numRows2 = $p_sql->rowCount();
                            $cartao2 = $p_sql->fetch(PDO::FETCH_ASSOC);

                            if ($numRows2) {
                                echo $mensagem = "<h4 style='color:red;'>Causa: Código de Documento e Autorização invertidos !!<br/>" .
                                "Parcela: {$parcela}/{$totalParcelas}</h4>";
                            } else {

                                $sql2 = "SELECT
                                c.id_cad_cartao,
                                c.data_da_conta,
                                c.operadora_do_cartao,
                                c.debito_ou_credito,
                                c.quantidade_de_parcelas,
                                c.titular_do_cartao,
                                c.valor_da_conta,
                                c.origem_da_conta,
                                c.contrato,
                                c.{$parcelaDesc},
                                c.{$parcelaRecebidoDesc},
                                c.{$parcelaStatusDesc},
                                c.{$parcelaCustoDesc},
                                c.{$parcelaDataDesc},
                                c.{$parcelaDataBaixaDesc},
                                c.{$parcelaUsuarioBaixaDesc},
                                IF(c.{$parcelaRecebidoDesc} > 0, 1, 0) as StatusParcela
                        FROM
                            cartao_de_credito c
                        WHERE
                            c.documento = :nsuDoc
                        AND CAST(c.{$parcelaDesc} AS DECIMAL) = CAST(:valor AS DECIMAL)";
                                $p_sql = $db->prepare($sql2);
                                $p_sql->bindValue(":nsuDoc", $nsuDoc);
                                $p_sql->bindValue(":valor", $valor);
                                $p_sql->execute();
                                $numRows2 = $p_sql->rowCount();
                                $cartao2 = $p_sql->fetch(PDO::FETCH_ASSOC);

                                if ($numRows2) {
                                    echo $mensagem = "<h4 style='color:red;'>" .
                                            "Provável Causa: Código de Autorização incorreto !!<br/>" .
                                            "Pesquise pelo Número do Documento: {$nsuDoc}<br/>" .
                                            "Parcela: {$parcela}/{$totalParcelas}" .
                                            "</h4>";
                                } else {
                                    $sql2 = "SELECT
                                c.id_cad_cartao,
                                c.data_da_conta,
                                c.operadora_do_cartao,
                                c.debito_ou_credito,
                                c.quantidade_de_parcelas,
                                c.titular_do_cartao,
                                c.valor_da_conta,
                                c.origem_da_conta,
                                c.contrato,
                                c.{$parcelaDesc},
                                c.{$parcelaRecebidoDesc},
                                c.{$parcelaStatusDesc},
                                c.{$parcelaCustoDesc},
                                c.{$parcelaDataDesc},
                                c.{$parcelaDataBaixaDesc},
                                c.{$parcelaUsuarioBaixaDesc},
                                IF(c.{$parcelaRecebidoDesc} > 0, 1, 0) as StatusParcela
                        FROM
                            cartao_de_credito c
                        WHERE
                            c.autorizacao = :codigoAutorizacao
                        AND CAST(c.{$parcelaDesc} AS DECIMAL) = CAST(:valor AS DECIMAL)";
                                    $p_sql = $db->prepare($sql2);
                                    $p_sql->bindValue(":codigoAutorizacao", $codigoAutorizacao);
                                    $p_sql->bindValue(":valor", $valor);
                                    $p_sql->execute();
                                    $numRows2 = $p_sql->rowCount();
                                    $cartao2 = $p_sql->fetch(PDO::FETCH_ASSOC);

                                    if ($numRows2) {
                                        echo $mensagem = "<h4 style='color:red;'>Provável Causa: Código de Documento incorreto !!<br/>" .
                                                "Pesquise pelo Código de Autorização: {$codigoAutorizacao}<br/>" .
                                                "Parcela: {$parcela}/{$totalParcelas}</h4>";
                                    } else {

                                        $sql2 = "SELECT
                                c.id_cad_cartao,
                                c.data_da_conta,
                                c.operadora_do_cartao,
                                c.debito_ou_credito,
                                c.quantidade_de_parcelas,
                                c.titular_do_cartao,
                                c.valor_da_conta,
                                c.origem_da_conta,
                                c.contrato,
                                c.{$parcelaDesc},
                                c.{$parcelaRecebidoDesc},
                                c.{$parcelaStatusDesc},
                                c.{$parcelaCustoDesc},
                                c.{$parcelaDataDesc},
                                c.{$parcelaDataBaixaDesc},
                                c.{$parcelaUsuarioBaixaDesc},
                                IF(c.{$parcelaRecebidoDesc} > 0, 1, 0) as StatusParcela
                        FROM
                            cartao_de_credito c
                        WHERE
                            c.autorizacao = :codigoAutorizacao
                        AND c.documento = :nsuDoc ";
                                        $p_sql = $db->prepare($sql2);
                                        $p_sql->bindValue(":codigoAutorizacao", $codigoAutorizacao);
                                        $p_sql->bindValue(":nsuDoc", $nsuDoc);
                                        $p_sql->execute();
                                        $numRows2 = $p_sql->rowCount();
                                        $cartao2 = $p_sql->fetch(PDO::FETCH_ASSOC);
                                        if ($numRows2) {
                                            echo $mensagem = "<h4 style='color:red;'>Provável Causa: Valor cadastrado incorretamente !!<br/>" .
                                                    "Valor Comprovante:  {$valor}<br/>" .
                                                    "Valor Cadastrado: {$cartao2[$parcelaDesc]}<br/>" .
                                                    "Parcela: {$parcela}/{$totalParcelas}" .
                                                    "</h4>";
                                        } else {
                                            echo $mensagem = "<div style='color:red;text-align: left;'>" .
                                                    "<b>Provável Causa: Comprovante não cadastrado ou cadastrado incorretamente !!</b><br/>" .
                                                    "Consulte o número de Doc.:<b>{$nsuDoc}</b><br/>" .
                                                    "Código de Autorização: <b>{$codigoAutorizacao}</b> <br/>" .
                                                    "Para localiza-lo no relátorio de Cartões.</div>";
                                        }
                                    }
                                }
                            }

                            echo "</pre>";
                        }


                    } catch (\Exception $e) {
                        $params = $p_sql->debugDumpParams();
                        print_r($e);
                        var_dump($sql, $nsuDoc, $codigoAutorizacao, $valor, $params);
                        die();
                    }

                    $detalhesValor[] = $detalheCV->getValor();

                    $dataParcela = array(
                            'valor' => $valor,
                            'dtVenda' => $detalheCV->getDtVendaAjuste(),
                            'tid' => $detalheCV->getTid(),
                            'parcela' => $parcela,
                            'totalParcelas' => $totalParcelas,
                            'nsuDoc' => $nsuDoc,
                            'codigoAutorizacao' => $codigoAutorizacao,
                            'localizado' => $localizado,
                            'mensagem' => $mensagem
                    );

                    $calculaComissao = calculaComissao($valor, $taxaComissao);
                    $calculaLiquido = $valor - $calculaComissao;

                    $dataParcela['valorLiquido'] = $calculaLiquido;
                    $dataParcela['valorComissao'] = $calculaComissao;
                    $dataParcela['dtBaixa'] = $arquivo->getHeader()->getDtProcessamento();
                    $dataParcela['dtCaptura'] = $dtCaptura;

                    $valorArr = array_merge($dataParcela, $cartao);

                    $baixa = false;
                    if ($baixar && $localizado && isset($cartao['StatusParcela']) && $cartao['StatusParcela'] == 0 && count($cartao) > 0) {
                        $baixa = baixaParcela($db, $valorArr);
                    }

                    $valorArr['baixa'] = $baixa;

                    $valorPorComprovante[$cont]['valores'][] = $valorArr;
                }

                if (!$localizado) {
                    $valorArr['Bandeira'] = $valorPorComprovante[$cont]['Bandeira'];
                    $valorArr['IdentificadorProdutoDesc'] = $valorPorComprovante[$cont]['IdentificadorProdutoDesc'];
                    $valorPorComprovanteNaoEncontrados[$cont][] = $valorArr;
                }

            } else {

                if ($bruto > 0) {
                    $valorPorComprovante[$cont]['mensagem'] = 'Resumo de Operações sem CV\'s';
                    $countSemCVs++;
                    $sumSemCVs[] = $bruto;

                    $valorSemComprovante[$cont] = $valorPorComprovante[$cont];
                }
            }
            $cont++;
        }

        if (count($valorPorComprovante) == 0) {
            continue;
        }

        echo "<table width='100%' border='1' class='table table-bordered table-condensed'>";

        $dataArquivo = $arquivo->getHeader()->getDtProcessamento()->format('d/m/Y');

        echo "<tr>
            <td align='center' colspan='17'><b>{$dataArquivo} - Arquivo: {$filename}</b></td>
          </tr>";

        echo <<<EOF
    <tr style='background-color:#0B66DC;color: #FFF;'>
        <th>&nbsp;</th>
        <th>Bruto</th>
        <th>Comissão</th>
        <th>Líquido</th>
        <th>Data Pag.</th>
        <th>CVs</th>
        <th>Bandeira</th>
        <th>Produto</th>
        <th>Valor Comissão CV</th>
        <th>Valor Líquido CV</th>
        <th>Parcela</th>
        <th>Status</th>
        <th>Valor CV</th>
        <th>Contrato</th>
        <th>Titular Cartão</th>
        <th>Data Venda</th>
        <th>NsuDoc</th>
        <th>Autorização</th>
    </tr>
EOF;
        $countCVs = 1;

        foreach ($valorPorComprovante as $comprovante) {

            $colorComprovante = $color = '#FFFFFF';
            $colorComprovanteText = $colorText = '#000000';
            if (intval($comprovante['quantidadeCVs']) > 1) {
                $color = 'lightsteelblue';
                $colorText = '#5D5D5D';
                $colorComprovante = '#447FCC';
                $colorComprovanteText = '#FFF';
            }

            if (count($comprovante['valores']) == 0) {
                $color = '#E03C30';
                $colorText = '#FFF';
            }

            if (intval($comprovante['total']) < 0 && count($comprovante['valores']) == 0) {
                $color = 'orange';
                $colorText = '#FFF';
            } elseif (intval($comprovante['total']) < 0 && count($comprovante['valores']) > 0) {
                $color = '#4C9980';
                $colorText = '#FFF';
            }

            $comprovante['totalDesc'] = formatNumber($comprovante['total']);
            $comprovante['valorComissaoDesc'] = formatNumber($comprovante['valorComissao']);
            $comprovante['liquidoDesc'] = formatNumber($comprovante['liquido']);

            if ($comprovante['valores']) {

                $cont = 0;

                foreach ($comprovante['valores'] as $cv) {

                    $calculaComissao = calculaComissao($cv['valor'], $comprovante['taxaDeComissao']);
                    $calculaComissao = number_format( $calculaComissao, 2, '.', '' );
                    $calculaLiquido  = $cv['valor'] - $calculaComissao;

                    if (isset($cv['StatusParcela']) && $cv['StatusParcela'] == 1) {
                        $jaBaixados[] = ['valor' => $cv['valor'], 'liquido' => $calculaLiquido, 'comissao' => $calculaComissao];
                    }

                    $calculaComissao = number_format($calculaComissao, 2, ',', '');
                    $calculaLiquido = number_format($calculaLiquido, 2, ',', '');
                    $localizado = $cv['localizado'] ? ($cv['StatusParcela'] == 1 ? '<span style="color:orange;">Já Baixado</span>' : '<span style="color:green;">Encontrado</span>') : '<span style="color:#FFF;">Não Encontrado</span>';
                    $baixa = $cv['baixa'] ? ' - Baixado com sucesso' : '';
                    $colorComprovante = $cv['localizado'] ? $colorComprovante : 'red';
                    $cv['valorDesc'] = formatNumber($cv['valor']);

                    echo <<<EOF
                <tr>
EOF;
                    if ($cont == 0) {
                        echo <<<EOF
            <th style='background-color:{$color};color:{$colorText}'>{$countCVs}</th>
            <th style='background-color:{$color};color:{$colorText}'>{$comprovante['totalDesc']}</th>
            <th style='background-color:{$color};color:{$colorText}'>{$comprovante['valorComissaoDesc']}</th>
            <th style='background-color:{$color};color:{$colorText}'>{$comprovante['liquidoDesc']}</th>
            <th style='background-color:{$color};color:{$colorText}'>{$comprovante['dtPrevPagamento']->format('d/m/Y')}</th>
            <th style='background-color:{$color};color:{$colorText}'>{$comprovante['quantidadeCVs']}</th>
            <th style='background-color:{$color};color:{$colorText}'>{$comprovante['Bandeira']}/{$comprovante['codigoBandeira']}</th>
            <th style='background-color:{$color};color:{$colorText}'>{$comprovante['IdentificadorProdutoDesc']}</th>
EOF;
                    } else {
                        $countCVs++;
                        echo <<<EOF
                <th style='background-color:{$color};color:{$colorText}'>{$countCVs}</th>
                <th colspan='5'>&nbsp;</th>
                <th style='background-color:{$color};color:{$colorText}'>{$comprovante['Bandeira']}/{$comprovante['codigoBandeira']}</th>
                <th style='background-color:{$color};color:{$colorText}'>{$comprovante['IdentificadorProdutoDesc']}</th>
EOF;
                    }

                    $cont++;

                    $cv['contrato'] = isset($cv['contrato']) ? $cv['contrato'] : '';
                    $cv['titular_do_cartao'] = isset($cv['titular_do_cartao']) ? $cv['titular_do_cartao'] : '';
                    $cv['nsuDoc'] = isset($cv['nsuDoc']) ? $cv['nsuDoc'] : '';
                    $cv['codigoAutorizacao'] = isset($cv['codigoAutorizacao']) ? $cv['codigoAutorizacao'] : '';

                    echo <<<EOF
            <th style='background-color:{$colorComprovante};color:{$colorComprovanteText}'>{$calculaComissao}</th>
            <th style='background-color:{$colorComprovante};color:{$colorComprovanteText}'>{$calculaLiquido}</th>
            <th style='background-color:{$colorComprovante};color:{$colorComprovanteText}'>{$cv['parcela']}/{$cv['totalParcelas']}</th>
            <th style='background-color:{$colorComprovante};color:{$colorComprovanteText}'>{$localizado}{$baixa}</th>
            <th style='background-color:{$colorComprovante};color:{$colorComprovanteText}'>{$cv['valorDesc']}</th>
            <th style='background-color:{$colorComprovante};color:{$colorComprovanteText}'>{$cv['contrato']}</th>
            <th style='background-color:{$colorComprovante};color:{$colorComprovanteText}'>{$cv['titular_do_cartao']}</th>
            <th style='background-color:{$colorComprovante};color:{$colorComprovanteText}'>{$cv['dtVenda']->format('d/m/Y')}</th>
            <th style='background-color:{$colorComprovante};color:{$colorComprovanteText}'>{$cv['nsuDoc']}</th>
            <th style='background-color:{$colorComprovante};color:{$colorComprovanteText}'>{$cv['codigoAutorizacao']}</th>
        </tr>
EOF;

                }
            } else {

                $mensagem = $comprovante['total'] < 0 ? 'Tarifa' : 'Comprovante não consta no arquivo';

                echo <<<EOF
        <tr>
            <th style='background-color:{$color};color:{$colorText}'>{$countCVs}</th>
            <th style='background-color:{$color};color:{$colorText}'>{$comprovante['totalDesc']}</th>
            <th style='background-color:{$color};color:{$colorText}'>{$comprovante['valorComissaoDesc']}</th>
            <th style='background-color:{$color};color:{$colorText}'>{$comprovante['liquidoDesc']}</th>
            <th style='background-color:{$color};color:{$colorText}'>{$comprovante['dtPrevPagamento']->format('d/m/Y')}</th>
            <th style='background-color:{$color};color:{$colorText}'>{$comprovante['quantidadeCVs']}</th>
            <th style='background-color:{$color};color:{$colorText}'>{$comprovante['Bandeira']}/{$comprovante['codigoBandeira']}</th>
            <th style='background-color:{$color};color:{$colorText}'>{$comprovante['IdentificadorProdutoDesc']}</th>
            <th style='background-color:{$color};color:{$colorText}' colspan='10'>$mensagem</th>
        </tr>
EOF;
            }
            $countCVs++;
        }

        $resumos[$filename]['jaBaixados'] = ($jaBaixados);
        //echo 'Total de Cv\'s: ';
        $resumos[$filename]['totalCvs'] = array_sum($detalhesCount);

        //echo 'Total Bruto Resumo: ';
        $valorGeral += array_sum($valorBruto);
        $resumos[$filename]['valorBrutoRO'] = array_sum($valorBruto);

        //echo 'Total Bruto Comprovantes: ';
        $resumos[$filename]['valorBrutoCVs'] = array_sum($detalhesValor);

        //echo 'Total Liquido Resumo: ';
        $resumos[$filename]['valorLiquido'] = array_sum($valorLiquido);

        $valorGeralLiquido += array_sum($valorLiquido);
        $resumos[$filename]['valorComissao'] = array_sum($valorComissao);
        $valoresComissao[] = $resumos[$filename]['valorComissao'];

        //echo 'Total Cobranças: ';
        $valoresCobrancas[$filename] = array_sum($valorCobrancas);

        $resumos[$filename]['CountROsemCVs'] = 0;
        $resumos[$filename]['ValorROsemCVs'] = 0;
        $resumos[$filename]['OperacoesSemCVs'] = [];

        if (count($valorSemComprovante)) {
            //echo 'Operacoes sem Comprovantes: ';
            $countSemComprovantesGeral += ($countSemCVs);
            $resumos[$filename]['CountROsemCVs'] = ($countSemCVs);

            //echo 'Valor sem Comprovantes: ';
            $valorSemComprovantesGeral += array_sum($sumSemCVs);
            $resumos[$filename]['ValorROsemCVs'] = array_sum($sumSemCVs);

            //echo 'Operacoes sem Comprovantes: ';
            $resumos[$filename]['OperacoesSemCVs'] = ($valorSemComprovante);
        }

        $background = 'background-color:grey;text-align:right;';

        $cobranca = formatNumber($valoresCobrancas[$filename]);
        $valorBrutoDia = formatNumber($resumos[$filename]['valorBrutoRO']);
        $valorBrutoCVs = formatNumber($resumos[$filename]['valorBrutoCVs']);
        $valorComissaoDia = formatNumber($resumos[$filename]['valorComissao']);
        $valorLiquidoDia = formatNumber($resumos[$filename]['valorLiquido']);
        $valorSemCVDia = formatNumber($resumos[$filename]['ValorROsemCVs']);

        $resumos[$filename]['data'] = $dataArquivo;
        $resumos[$filename]['cobranca'] = $valoresCobrancas[$filename];

        $valoresCanceladosDesc = formatNumber(array_sum($valoresCancelados));

        $sumJaBaixados = array_sum(array_column($resumos[$filename]['jaBaixados'], 'valor'));
        $sumJaBaixadosDesc = formatNumber($sumJaBaixados);
        $sumJaBaixadosLiquido = array_sum(array_column($resumos[$filename]['jaBaixados'], 'liquido'));
        $sumJaBaixadosLiquidoDesc = formatNumber($sumJaBaixadosLiquido);
        $sumJaBaixadosComissao = array_sum(array_column($resumos[$filename]['jaBaixados'], 'comissao'));
        $sumJaBaixadosComissaoDesc = formatNumber($sumJaBaixadosComissao);
        $countJaBaixados = count($resumos[$filename]['jaBaixados']);

        $saldoABaixar = $resumos[$filename]['valorBrutoCVs'] - $sumJaBaixados;
        $saldoABaixarDesc = formatNumber($saldoABaixar);
        $saldoABaixarLiquido = $resumos[$filename]['valorLiquido'] - $sumJaBaixadosLiquido;
        $saldoABaixarLiquidoDesc =  formatNumber($saldoABaixarLiquido);
        $saldoABaixarComissao = $resumos[$filename]['valorComissao'] - (-$sumJaBaixadosComissao);
        $saldoABaixarComissaoDesc =  formatNumber($saldoABaixarComissao);
        $countSaldoCVS = $resumos[$filename]['totalCvs'] - $countJaBaixados;

    echo <<<EOF
    <tr>
        <td style='background-color:#0B66DC;color: #FFF;' colspan=6>Resumo do Dia</td>
    </tr>
    <tr style='{$background}'>
        <th colspan='5'>Data</th>
        <th style='text-align:right;'>{$dataArquivo}</th>
    </tr>
    <tr style='{$background}'>
        <th colspan='5'>Quantidade de CV's</th>
        <th style='text-align:right;'>{$resumos[$filename]['totalCvs']}</th>
    </tr>
    <tr style='{$background}'>
        <th colspan='5'>Valor Bruto Resumo de Operações</th>
        <th style='text-align:right;'>{$valorBrutoDia}</th>
    </tr>
    <tr style='{$background}'>
        <th colspan='5'>Valor Bruto de CV's</th>
        <th style='text-align:right;'>{$valorBrutoCVs}</th>
    </tr>
    <tr style='{$background}'>
        <th colspan='5'>Valor Comissão</th>
        <th style='text-align:right;'>{$valorComissaoDia}</th>
    </tr>
    <tr style='{$background}'>
        <th colspan='5'>Valor Líquido Depositado</th>
        <th style='text-align:right;'>{$valorLiquidoDia}</th>
    </tr>
    <tr style='{$background}'>
        <th colspan='5'>Operações sem CV's</th>
        <th style='text-align:right;'>{$resumos[$filename]['CountROsemCVs']}</th>
    </tr>
    <tr style='{$background}'>
        <th colspan='5'>Valor sem CV's</th>
        <th style='text-align:right;'>{$valorSemCVDia}</th>
    </tr>
    <tr style='{$background};text-align:right;'>
        <th colspan='5'>Cobranças</th>
        <th style='text-align:right;background-color:red;'>{$cobranca}</th>
    </tr>
    <tr style='{$background};text-align:right;'>
        <th colspan='5'>Cancelamentos</th>
        <th style='text-align:right;background-color:#4C9980;'>{$valoresCanceladosDesc}</th>
    </tr>

    <tr style='{$background};text-align:right;'>
        <th colspan='5'>Quantidade de CV's já Baixados</th>
        <th style='text-align:right;background-color:#CCC;'>{$countJaBaixados}</th>
    </tr>
    <tr style='{$background};text-align:right;'>
        <th colspan='5'>Valor Bruto já Baixado</th>
        <th style='text-align:right;background-color:#CCC;'>{$sumJaBaixadosDesc}</th>
    </tr>
    <tr style='{$background};text-align:right;'>
        <th colspan='5'>Valor Bruto já Baixado Líquido</th>
        <th style='text-align:right;background-color:#CCC;'>{$sumJaBaixadosLiquidoDesc}</th>
    </tr>
    <tr style='{$background};text-align:right;'>
        <th colspan='5'>Valor Bruto já Baixado Comissão</th>
        <th style='text-align:right;background-color:#CCC;'>{$sumJaBaixadosComissaoDesc}</th>
    </tr>

    <tr style='{$background};text-align:right;'>
        <th colspan='5'>Quantidade de CV's a baixar</th>
        <th style='text-align:right;background-color:#FBDEDE;'>{$countSaldoCVS}</th>
    </tr>
    <tr style='{$background};text-align:right;'>
        <th colspan='5'>Valor Bruto a baixar</th>
        <th style='text-align:right;background-color:#FBDEDE;'>{$saldoABaixarDesc}</th>
    </tr>
    <tr style='{$background};text-align:right;'>
        <th colspan='5'>Valor Bruto a baixar Liquido</th>
        <th style='text-align:right;background-color:#FBDEDE;'>{$saldoABaixarLiquidoDesc}</th>
    </tr>
    <tr style='{$background};text-align:right;'>
        <th colspan='5'>Valor Bruto a baixar Comissão</th>
        <th style='text-align:right;background-color:#FBDEDE;'>{$saldoABaixarComissaoDesc}</th>
    </tr>
EOF;

        if (!is_null($det = $arquivo->getDetalheAntecipacao())) {
            try {
                $valorAntecipacao = $det->getValorBrutoAntecipacaoTotal();
                $valorLiquidoAntecipacaoTotal = $det->getValorLiquidoAntecipacaoTotal();
                $diff = $valorAntecipacao - $valorLiquidoAntecipacaoTotal;

                $resumos[$filename]['valorComissao'] = -($resumos[$filename]['valorComissao']);
                $resumos[$filename]['valorLiquido'] = $resumos[$filename]['valorLiquido'] - $diff;

                $valorCobrancas[] = -($diff);
                $valoresCobrancas[$filename] = array_sum($valorCobrancas);
                $resumos[$filename]['cobranca'] = $valoresCobrancas[$filename];

                $valorAntecipacaoDesc = formatNumber($valorAntecipacao);
                $diffDesc = formatNumber($diff);
                $valorLiquidoAntecipacaoTotalDesc = formatNumber($valorLiquidoAntecipacaoTotal);
                echo <<<EOF
            <tr style='color:#000;text-align:right;'>
                <th colspan='5'>Valor Bruto Antecipação</th>
                <th style='text-align:right;background-color:#000;color:#FFF'>{$valorAntecipacaoDesc}</th>
            </tr>
            <tr style='color:#000;text-align:right;'>
                <th colspan='5'>Taxa Antecipação</th>
                <th style='text-align:right;background-color:#000;color:#FFF'>{$diffDesc}</th>
            </tr>
            <tr style='color:#000;text-align:right;'>
                <th colspan='5'>Valor Líquido Antecipação</th>
                <th style='text-align:right;background-color:#000;color:#FFF'>{$valorLiquidoAntecipacaoTotalDesc}</th>
            </tr>
EOF;
            } catch (\Exception $e) {
                var_dump($e);
            }
        }

        echo "</table>";

        $valoresCancelados = [];
    } catch (\Exception $e) {

        echo "<div class='col-md-12' style='color:red !important;'>";
        echo "Arquivo: $filename";
        echo "<br/>";
        echo "Erro: ", $e->getMessage();
        echo "<br/>";
        echo "Linha: ", $e->getLine();
        echo "<br/>";
        echo "Linha: ", $e->getFile();
        echo "<hr/>";
        echo "</div>";

    }
}

echo '<table width="100%" border="1"  class="table table-bordered table-condensed table-striped">';

echo "<caption>Resumo</caption>";

echo <<<EOF
    <tr style='background-color:#0B66DC;color: #FFF;'>
        <th>Data</th>
        <th>Quantidade de CV's</th>
        <th>Valor Líquido Depositado</th>
        <th>Valor Comissão</th>
        <th>Valor Bruto Resumo de Operações</th>
        <th>Valor Bruto de CV's</th>
        <th>Operações sem CV's</th>
        <th>Valor sem CV's</th>
        <th>Cobranças</th>
    </tr>
EOF;

$sumTotalCvs = 0;
$sumValorBrutoRO = 0;
$sumValorBrutoCVs = 0;
$sumValorComissao = 0;
$sumValorLiquido = 0;
$sumCountROsemCVs = 0;
$sumValorROsemCVs = 0;
$sumCobranca = 0;

foreach ($resumos as $resumo) {

    if ($resumo['valorLiquido'] > 0) {
        $sumTotalCvs += $resumo['totalCvs'];
        $sumValorBrutoRO += $resumo['valorBrutoRO'];
        $sumValorBrutoCVs += $resumo['valorBrutoCVs'];
        $sumValorComissao += $resumo['valorComissao'];
        $sumValorLiquido += $resumo['valorLiquido'];
        $sumCountROsemCVs += $resumo['CountROsemCVs'];
        $sumValorROsemCVs += $resumo['ValorROsemCVs'];
        $sumCobranca += $resumo['cobranca'];

        $resumo['valorBrutoRODesc'] = formatNumber($resumo['valorBrutoRO']);
        $resumo['valorBrutoCVsDesc'] = formatNumber($resumo['valorBrutoCVs']);
        $resumo['valorComissaoDesc'] = formatNumber($resumo['valorComissao']);
        $resumo['valorLiquidoDesc'] = formatNumber($resumo['valorLiquido']);
        $resumo['ValorROsemCVsDesc'] = formatNumber($resumo['ValorROsemCVs']);
        $resumo['cobrancaDesc'] = formatNumber($resumo['cobranca']);

        echo <<<EOF
        <tr style='text-align:right !important;'>
            <td style='text-align:right;'>{$resumo['data']}</td>
            <td style='text-align:right;'>{$resumo['totalCvs']}</td>
            <td style='text-align:right;'>{$resumo['valorLiquidoDesc']}</td>
            <td style='text-align:right;'>{$resumo['valorComissaoDesc']}</td>
            <td style='text-align:right;'>{$resumo['valorBrutoRODesc']}</td>
            <td style='text-align:right;'>{$resumo['valorBrutoCVsDesc']}</td>
            <td style='text-align:right;'>{$resumo['CountROsemCVs']}</td>
            <td style='text-align:right;'>{$resumo['ValorROsemCVsDesc']}</td>
            <td style='text-align:right;'>{$resumo['cobrancaDesc']}</td>
        </tr>
EOF;
    }
}

$sumValorBrutoRODesc = formatNumber($sumValorBrutoRO);
$sumValorBrutoCVsDesc = formatNumber($sumValorBrutoCVs);
$sumValorComissaoDesc = formatNumber($sumValorComissao);
$sumValorLiquidoDesc = formatNumber($sumValorLiquido);
$sumValorROsemCVsDesc = formatNumber($sumValorROsemCVs);
$sumCobrancaDesc = formatNumber($sumCobranca);

echo <<<EOF
        <tr style='text-align:right !important;background-color:gray;'>
            <th>&nbsp;</th>
            <th style='text-align:right;'>{$sumTotalCvs}</th>
            <th style='text-align:right;'>{$sumValorLiquidoDesc}</th>
            <th style='text-align:right;color: #C30B0B;'>{$sumValorComissaoDesc}</th>
            <th style='text-align:right;'>{$sumValorBrutoRODesc}</th>
            <th style='text-align:right;'>{$sumValorBrutoCVsDesc}</th>
            <th style='text-align:right;'>{$sumCountROsemCVs}</th>
            <th style='text-align:right;'>{$sumValorROsemCVsDesc}</th>
            <th style='text-align:right; color: #C30B0B;'>{$sumCobrancaDesc}</th>
        </tr>
EOF;

$sumBruto = $sumValorBrutoCVs + $sumValorROsemCVs;
$sumBrutoDesc = formatNumber($sumBruto);

echo <<<EOF
        <tr style='text-align:right !important;background-color:#0B66DC;color: #FFF;'>
            <th colspan='5'>&nbsp;</th>
            <th>Total Bruto</th>
            <th colspan='3' style='text-align: center;'>{$sumBrutoDesc}</th>
        </tr>
EOF;

echo '</table>';

if (count($valorPorComprovanteNaoEncontrados)) {
    echo "<hr/>";
    echo "<table width='100%' border='1'  class='table table-bordered table-condensed'>";
    echo "<caption>Cartões não encontrados</caption>";
    echo <<<EOF
                <tr  style='background-color:#0B66DC;color: #FFF;'>
                    <th>&nbsp;</th>
                    <th>Valor</th>
                    <th>Data Venda</th>
                    <th>Parcela</th>
                    <th>Documento</th>
                    <th>Autorização</th>
                    <th>Mensagem</th>
                    <th>Bandeira</th>
                    <th>Produto</th>
                </tr>
EOF;

    $cont = 1;
    foreach ($valorPorComprovanteNaoEncontrados as $resumo) {
        foreach ($resumo as $valorCV) {
            echo <<<EOF
                <tr>
                    <th style='text-align:right;'>{$cont}</th>
                    <th style='text-align:right;'>{$valorCV['valor']}</th>
                    <th style='text-align:right;'>{$valorCV['dtVenda']->format('d/m/Y')}</th>
                    <th style='text-align:right;'>{$valorCV['parcela']}/{$valorCV['totalParcelas']}</th>
                    <th style='text-align:right;'>{$valorCV['nsuDoc']}</th>
                    <th style='text-align:right;'>{$valorCV['codigoAutorizacao']}</th>
                    <th style='text-align:right;'>{$valorCV['mensagem']}</th>
                    <th style='text-align:right;'>{$valorCV['Bandeira']}</th>
                    <th style='text-align:right;'>{$valorCV['IdentificadorProdutoDesc']}</th>
                </tr>
EOF;
            $cont++;
        }
    }
    echo "</table>";

    //echo "</pre>";
}

if (count($resumos)) {
    $countSemCVS = 0;
    foreach ($resumos as $resumo) {
        foreach ($resumo['OperacoesSemCVs'] as $op) {
            $countSemCVS++;
        }
    }

    if ($countSemCVS) {
        echo "<hr/>";
        echo "<table width='100%' border='1'  class='table table-bordered table-condensed'>";
        echo "<caption>Comprovantes Faltantes</caption>";
        echo <<<EOF
    <tr  style='background-color:#0B66DC;color: #FFF;'>
        <th>&nbsp;</th>
        <th>Valor</th>
        <th>Comissao</th>
        <th>Líquido</th>
        <th>Data Venda</th>
        <th>Data Pagamento</th>
        <th>Taxa %</th>
        <th>Bandeira</th>
        <th>Numero RO</th>
        <th>Tipo</th>
    </tr>
EOF;
        $cont = 1;
        foreach ($resumos as $resumo) {
            foreach ($resumo['OperacoesSemCVs'] as $op) {
                $valorComissao_ = number_format($op['valorComissao'], 2, ',', '');
                $valorLiquido_ = number_format($op['liquido'], 2, ',', '');
                $valorTotal_ = number_format($op['total'], 2, ',', '');
                echo <<<EOF
    <tr>
        <th style='text-align:right;'>{$cont}</th>
        <th style='text-align:right;'>{$valorTotal_}</th>
        <th style='text-align:right;'>{$valorComissao_}</th>
        <th style='text-align:right;'>{$valorLiquido_}</th>
        <th style='text-align:right;'>{$op['dtCaptura']->format('d/m/Y')}</th>
        <th style='text-align:right;'>{$op['dtPrevPagamento']->format('d/m/Y')}</th>
        <th style='text-align:right;'>{$op['taxaDeComissao']}</th>
        <th>{$op['Bandeira']} - {$op['codigoBandeira']}</th>
        <th>{$op['numeroRO']}</th>
        <th>{$op['IdentificadorProdutoDesc']} - {$op['IdentificadorProduto']}</th>
    </tr>
EOF;
                $cont++;
            }
        }
        echo "</table>";
    }
}
echo "</div>";
echo "</div>";
echo "</div>";

} catch (\Exception $e) {
    dd($e);
}
?>
```
