<?php

class DataHandlerFusioni implements OpenPADataHandlerInterface
{
    private $oCMapsController;

    public function __construct( array $Params )
    {
        $this->oCMapsController = new OCMapsController();
        $this->oCMapsController->init('1345');

    }

    public function getData()
    {
        $returnArray = array();

        $returnArray['geoDataAsArray'] = $this->oCMapsController->geoDataAsArray;
        $returnArray['geoDataForFitBounds'] = $this->oCMapsController->geoDataForFitBounds;
        $returnArray['geoDataStatusArray'] = $this->oCMapsController->geoDataStatusArray;

        return $returnArray;
    }
}