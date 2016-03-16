<?php
namespace EdiParser\Arquivo;

class DetalheROAntecipacao extends LinhaAbstract
{
    /**
     * @var array
     */
    protected $posicoes = array(
        'dtVencimentoRO'=> array('i'=>21,'t'=>8,'type'=>'date'),
        'numeroRO'=>array('i'=>29,'t'=>7),
        'parcelaAntecipada'=>array('i'=>36,'t'=>2),
        'totalParcelas'=>array('i'=>38,'t'=>2),
        'valorBruto'=>array('i'=>40,'t'=>14,'type'=>'currency'),
        'valorLiquido'=>array('i'=>54,'t'=>14,'type'=>'currency'),
        'valorBrutoAntecipacaoRO'=>array('i'=>68,'t'=>14,'type'=>'currency'),
        'valorLiquidoAntecipacaoRO'=>array('i'=>82,'t'=>14,'type'=>'currency'),
        'codigoBandeira'=> array('i'=>96,'t'=>3),
        'numeroUnicoRO'=> array('i'=>99,'t'=>22),
    );

    protected $dtVencimentoRO;

    protected $numeroRO;

    protected $parcelaAntecipada;

    protected $totalParcelas;

    /**
     * @return mixed
     */
    public function getDtVencimentoRO()
    {
        return $this->dtVencimentoRO;
    }

    /**
     * @param mixed $dtVencimentoRO
     */
    public function setDtVencimentoRO($dtVencimentoRO)
    {
        $this->dtVencimentoRO = $dtVencimentoRO;
    }

    /**
     * @return mixed
     */
    public function getNumeroRO()
    {
        return $this->numeroRO;
    }

    /**
     * @param mixed $numeroRO
     */
    public function setNumeroRO($numeroRO)
    {
        $this->numeroRO = $numeroRO;
    }

    /**
     * @return mixed
     */
    public function getParcelaAntecipada()
    {
        return $this->parcelaAntecipada;
    }

    /**
     * @param mixed $parcelaAntecipada
     */
    public function setParcelaAntecipada($parcelaAntecipada)
    {
        $this->parcelaAntecipada = $parcelaAntecipada;
    }

    /**
     * @return mixed
     */
    public function getTotalParcelas()
    {
        return $this->totalParcelas;
    }

    /**
     * @param mixed $totalParcelas
     */
    public function setTotalParcelas($totalParcelas)
    {
        $this->totalParcelas = $totalParcelas;
    }

    /**
     * @return mixed
     */
    public function getValorBruto()
    {
        return $this->valorBruto;
    }

    /**
     * @param mixed $valorBruto
     */
    public function setValorBruto($valorBruto)
    {
        $this->valorBruto = $valorBruto;
    }

    /**
     * @return mixed
     */
    public function getValorLiquido()
    {
        return $this->valorLiquido;
    }

    /**
     * @param mixed $valorLiquido
     */
    public function setValorLiquido($valorLiquido)
    {
        $this->valorLiquido = $valorLiquido;
    }

    /**
     * @return mixed
     */
    public function getValorBrutoAntecipacaoRO()
    {
        return $this->valorBrutoAntecipacaoRO;
    }

    /**
     * @param mixed $valorBrutoAntecipacaoRO
     */
    public function setValorBrutoAntecipacaoRO($valorBrutoAntecipacaoRO)
    {
        $this->valorBrutoAntecipacaoRO = $valorBrutoAntecipacaoRO;
    }

    /**
     * @return mixed
     */
    public function getValorLiquidoAntecipacaoRO()
    {
        return $this->valorLiquidoAntecipacaoRO;
    }

    /**
     * @param mixed $valorLiquidoAntecipacaoRO
     */
    public function setValorLiquidoAntecipacaoRO($valorLiquidoAntecipacaoRO)
    {
        $this->valorLiquidoAntecipacaoRO = $valorLiquidoAntecipacaoRO;
    }

    /**
     * @return mixed
     */
    public function getCodigoBandeira()
    {
        return $this->codigoBandeira;
    }

    /**
     * @param mixed $codigoBandeira
     */
    public function setCodigoBandeira($codigoBandeira)
    {
        $this->codigoBandeira = $codigoBandeira;
    }

    /**
     * @return mixed
     */
    public function getNumeroUnicoRO()
    {
        return $this->numeroUnicoRO;
    }

    /**
     * @param mixed $numeroUnicoRO
     */
    public function setNumeroUnicoRO($numeroUnicoRO)
    {
        $this->numeroUnicoRO = $numeroUnicoRO;
    }

    protected $valorBruto;

    protected $valorLiquido;

    protected $valorBrutoAntecipacaoRO;

    protected $valorLiquidoAntecipacaoRO;

    protected $codigoBandeira;

    protected $numeroUnicoRO;

    // -- Methods prevent errors --//

    public  function getDtPrevPagamento()
    {
       return $this->getDtVencimentoRO();
    }

    public function getQuantidadeCVs(){
        return 1;
    }

    public function getValorComissao()
    {
        return $this->getValorBruto() - $this->getValorLiquido();
    }

    public function getTaxaDeComissao()
    {
        return ( $this->getValorComissao() * 100) / $this->getValorBruto();
    }

    public function getTarifa()
    {
        return '';
    }

    public function getIdentificadorProduto()
    {
        return '';
    }

    public function getIdentificadorProdutoDesc()
    {
        return '';
    }

    public function getDtCaptura()
    {
        return $this->getDtVencimentoRO();
    }

    //---//

    protected $detalhesCV = array();


    /**
     * @param DetalheCV $detalheCV
     */
    public function addDetalheCV(DetalheCV $detalheCV)
    {
        $this->detalhesCV[] = $detalheCV;
    }


    /**
     * @return array
     */
    public function getDetalhesCV()
    {
        return $this->detalhesCV;
    }
}
