<?php
namespace EdiParser\Arquivo;

class DetalheAntecipacao extends LinhaAbstract
{
    /**
     * @var array
     */
    protected $posicoes = array(
            'numeroRO'=>array('i'=>12,'t'=>9),
            'dtCreditoDaOperacao'=> array('i'=>21,'t'=>8,'type'=>'date'),
            'valorBruto'=>array('i'=>29,'t'=>14,'type'=>'currency'),
            'valorBrutoAntecipacao'=>array('i'=>43,'t'=>14,'type'=>'currency'),
            'valorBrutoAntecipadoEletron'=>array('i'=>57,'t'=>14,'type'=>'currency'),
            'valorBrutoAntecipacaoTotal'=>array('i'=>71,'t'=>14,'type'=>'currency'),
            'valorLiquidoAntecipacaoAVista'=>array('i'=>85,'t'=>14,'type'=>'currency'),
            'valorLiquidoAntecipacaoParcelado'=>array('i'=>99,'t'=>14,'type'=>'currency'),
            'valorLiquidoAntecipacaoPre'=>array('i'=>113,'t'=>14,'type'=>'currency'),
            'valorLiquidoAntecipacaoTotal'=>array('i'=>127,'t'=>14,'type'=>'currency'),
            'taxaDesconto' => array('i'=>141,'t'=>5),
            'codigoDoBanco' => array('i'=>146,'t'=>4),
            'codigoAgencia' => array('i'=>150,'t'=>5),
            'codigoContaCorrente' => array('i'=>155,'t'=>14),
            'valorLiquidoAntecipacao'=>array('i'=>169,'t'=>14,'type'=>'currency'),
            'usoCielo' => array('i'=>183,'t'=>68),
    );


    protected $numeroRO;
    protected $dtCreditoDaOperacao;
    protected $valorBruto;
    protected $valorBrutoAntecipacao;
    protected $valorBrutoAntecipadoEletron;
    protected $valorBrutoAntecipacaoTotal;
    protected $valorLiquidoAntecipacaoAVista;
    protected $valorLiquidoAntecipacaoParcelado;
    protected $valorLiquidoAntecipacaoPre;
    protected $valorLiquidoAntecipacaoTotal;
    protected $taxaDesconto;
    protected $codigoDoBanco;
    protected $codigoAgencia;
    protected $codigoContaCorrente;
    protected $valorLiquidoAntecipacao;
    protected $usoCielo;

    /**
     * @var array
     */
    protected $detalhesRO = array();
    /**
     * @param DetalheCV $detalheCV
     */
    public function addDetalheCV(DetalheRoAntecipado $detalheRO)
    {
        $this->detalhesRO[] = $detalheRO;
    }

    /**
     * @return array
     */
    public function getDetalhesRO()
    {
        return $this->detalhesRO;
    }

    /**
     * @return mixed
     */
    public function getUsoCielo()
    {
        return $this->usoCielo;
    }

    /**
     * @param mixed $usoCielo
     */
    public function setUsoCielo($usoCielo)
    {
        $this->usoCielo = $usoCielo;
    }

    /**
     * @return array
     */
    public function getPosicoes()
    {
        return $this->posicoes;
    }

    /**
     * @param array $posicoes
     */
    public function setPosicoes($posicoes)
    {
        $this->posicoes = $posicoes;
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
    public function getDtCreditoDaOperacao()
    {
        return $this->dtCreditoDaOperacao;
    }

    /**
     * @param mixed $dtCreditoDaOperacao
     */
    public function setDtCreditoDaOperacao($dtCreditoDaOperacao)
    {
        $this->dtCreditoDaOperacao = $dtCreditoDaOperacao;
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
    public function getValorBrutoAntecipacao()
    {
        return $this->valorBrutoAntecipacao;
    }

    /**
     * @param mixed $valorBrutoAntecipacao
     */
    public function setValorBrutoAntecipacao($valorBrutoAntecipacao)
    {
        $this->valorBrutoAntecipacao = $valorBrutoAntecipacao;
    }

    /**
     * @return mixed
     */
    public function getValorBrutoAntecipadoEletron()
    {
        return $this->valorBrutoAntecipadoEletron;
    }

    /**
     * @param mixed $valorBrutoAntecipadoEletron
     */
    public function setValorBrutoAntecipadoEletron($valorBrutoAntecipadoEletron)
    {
        $this->valorBrutoAntecipadoEletron = $valorBrutoAntecipadoEletron;
    }

    /**
     * @return mixed
     */
    public function getValorBrutoAntecipacaoTotal()
    {
        return $this->valorBrutoAntecipacaoTotal;
    }

    /**
     * @param mixed $valorBrutoAntecipacaoTotal
     */
    public function setValorBrutoAntecipacaoTotal($valorBrutoAntecipacaoTotal)
    {
        $this->valorBrutoAntecipacaoTotal = $valorBrutoAntecipacaoTotal;
    }

    /**
     * @return mixed
     */
    public function getValorLiquidoAntecipacaoAVista()
    {
        return $this->valorLiquidoAntecipacaoAVista;
    }

    /**
     * @param mixed $valorLiquidoAntecipacaoAVista
     */
    public function setValorLiquidoAntecipacaoAVista($valorLiquidoAntecipacaoAVista)
    {
        $this->valorLiquidoAntecipacaoAVista = $valorLiquidoAntecipacaoAVista;
    }

    /**
     * @return mixed
     */
    public function getValorLiquidoAntecipacaoParcelado()
    {
        return $this->valorLiquidoAntecipacaoParcelado;
    }

    /**
     * @param mixed $valorLiquidoAntecipacaoParcelado
     */
    public function setValorLiquidoAntecipacaoParcelado($valorLiquidoAntecipacaoParcelado)
    {
        $this->valorLiquidoAntecipacaoParcelado = $valorLiquidoAntecipacaoParcelado;
    }

    /**
     * @return mixed
     */
    public function getValorLiquidoAntecipacaoPre()
    {
        return $this->valorLiquidoAntecipacaoPre;
    }

    /**
     * @param mixed $valorLiquidoAntecipacaoPre
     */
    public function setValorLiquidoAntecipacaoPre($valorLiquidoAntecipacaoPre)
    {
        $this->valorLiquidoAntecipacaoPre = $valorLiquidoAntecipacaoPre;
    }

    /**
     * @return mixed
     */
    public function getValorLiquidoAntecipacaoTotal()
    {
        return $this->valorLiquidoAntecipacaoTotal;
    }

    /**
     * @param mixed $valorLiquidoAntecipacaoTotal
     */
    public function setValorLiquidoAntecipacaoTotal($valorLiquidoAntecipacaoTotal)
    {
        $this->valorLiquidoAntecipacaoTotal = $valorLiquidoAntecipacaoTotal;
    }

    /**
     * @return mixed
     */
    public function getTaxaDesconto()
    {
        return $this->taxaDesconto;
    }

    /**
     * @param mixed $taxaDesconto
     */
    public function setTaxaDesconto($taxaDesconto)
    {
        $this->taxaDesconto = $taxaDesconto;
    }

    /**
     * @return mixed
     */
    public function getCodigoDoBanco()
    {
        return $this->codigoDoBanco;
    }

    /**
     * @param mixed $codigoDoBanco
     */
    public function setCodigoDoBanco($codigoDoBanco)
    {
        $this->codigoDoBanco = $codigoDoBanco;
    }

    /**
     * @return mixed
     */
    public function getCodigoAgencia()
    {
        return $this->codigoAgencia;
    }

    /**
     * @param mixed $codigoAgencia
     */
    public function setCodigoAgencia($codigoAgencia)
    {
        $this->codigoAgencia = $codigoAgencia;
    }

    /**
     * @return mixed
     */
    public function getCodigoContaCorrente()
    {
        return $this->codigoContaCorrente;
    }

    /**
     * @param mixed $codigoContaCorrente
     */
    public function setCodigoContaCorrente($codigoContaCorrente)
    {
        $this->codigoContaCorrente = $codigoContaCorrente;
    }

    /**
     * @return mixed
     */
    public function getValorLiquidoAntecipacao()
    {
        return $this->valorLiquidoAntecipacao;
    }

    /**
     * @param mixed $valorLiquidoAntecipacao
     */
    public function setValorLiquidoAntecipacao($valorLiquidoAntecipacao)
    {
        $this->valorLiquidoAntecipacao = $valorLiquidoAntecipacao;
    }

}
