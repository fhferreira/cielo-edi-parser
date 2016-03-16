<?php
namespace EdiParser\Arquivo;

abstract class LinhaAbstract
{
	/**
	 * @var array
     */
	protected $posicoes = array(
		//	'estabelecimentoMatriz'=>array('i'=>2,'t'=>11),
    	//	'dtProcessamento'=>array('i'=>12,'t'=>19,'type'=>'date')
    );

    /**
     * @var array
     */
    protected $bandeiras = array(
        "001" => "VISA",
        "002" => "Mastercard",
        "006" => "SoroCred",
        "007" => "ELO",
        "009" => "Diners",
        "011" => "Agiplan",
        "015" => "Banescard",
        "023" => "Cabal",
        "029" => "Credsystem",
        "035" => "Esplanada",
        "064" => "Credz",
    );

    /**
     * @param $codigoBandeira
     * @return mixed
     */
    public function getBandeira($codigoBandeira)
    {
        return isset($this->bandeiras[$codigoBandeira]) ? $this->bandeiras[$codigoBandeira] : $codigoBandeira;
    }

	/**
	 * LinhaAbstract constructor.
	 * @param $linha
     */
	public function __construct($linha)
    {
        foreach ($this->posicoes as $campo => $pos) {
            $setter = 'set'. ucfirst($campo);
            $valor = substr($linha, $pos['i']-1, $pos['t']);
            if (isset($pos['type'])) {
                if ($pos['type'] == 'date') {
                    $valor = \DateTime::createFromFormat('Ymd', $valor);
                } elseif ($pos['type'] == 'smalldate') {
                    $valor = \DateTime::createFromFormat('ymd', $valor);
                } elseif ($pos['type'] == 'currency') {
                    $valor = (float)($valor/100);
                }
            }
        
            $this->$setter($valor);
        }
    }
}
