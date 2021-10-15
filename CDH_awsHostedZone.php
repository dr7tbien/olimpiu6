<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    require_once 'classes/CDH_abstractAwsService.php';
    require_once 'classes/CDH_awsVpc.php';
    require_once 'defines/services_aws/CDH_awsDefineHostedZone.php';
    
    class CDH_awsHostedZone extends CDH_abstractAwsService {
    

        public function __construct(){
            $this->setCredentials();
            $this->setClientR53();
            $this->setClientEc2();
            $this->setClassName("CDH_awsHostedZone");
            
        }














        public function create ( $data = [] ) {

            $client = $this->getClientRds();
            #$id     = $data['DBInstanceIdentifier'];
            $name   = $data['Name'];
            
            try{
                if ( isset($id) && $this->existsById( $id ) )
                    throw new Exception( "L044 El servicio " . $this->getClassName() . " con la id " . $id . " ya existe");
                
                if ( isset($name) && $this->existsByName( $name ) )
                    throw new Exception( "L047 El servicio " . $this->getClassName() . " con el nombre " . $name . " ya existe");
                
                $result     = $client->createHostedZone( $data );
                $service    = $result['HostedZone'];
                #$identifier = $service['DBClusterParameterGroupName'];

                #if( $this->waitToBeCreated(  $identifier  ) == false )
                #    throw new Exception( "L054 Demasiado tiempo esperando a la creación del servicio " . $this->getClassName() );
                
                $result = $client->changeTagsForResource([
                    'AddTags' => [
                        [
                            'Key' => 'Name',
                            'Value' => $service['Name'],
                        ],
                        // ...
                    ],
                    'ResourceId' => str_replace('/hostedzone/', '', $service['Id'] ),
                    'ResourceType' => 'hostedzone', // REQUIRED
                ]);
                
                return true;
            
            }catch (AwsException $e) {
                $this->logAdmin(  "L059 Error  " . $this->getClassName() . "->create()" );  
                $this->logAdmin(  "\n Mensaje de error:" );
                $this->logAdmin(  $e->getMessage() );
                return false;
            }catch(Exception $e){
                $this->logAdmin(  "L064 Error  " . $this->getClassName() . "->create()" );  
                $this->logAdmin(  "\n Mensaje de error:" );
                $this->logAdmin(  $e->getMessage() );
                return false;
            }

        }




        public function getResourceRecordSets( $name ){
            $service    = $this->getServiceByName( $name );
            $client     = $this->getClientR53();

            $result = $client->listResourceRecordSets([
                'HostedZoneId' => str_replace('/hostedzone/', '', $service['Id'] ), // REQUIRED
                #'StartRecordName' => 'NS',
            ]);

            return $result['ResourceRecordSets'];
            
        }


        public function getRecordSetsNs( $name ){
            $service    = $this->getServiceByName( $name );
            $client     = $this->getClientR53();

            $result = $client->listResourceRecordSets([
                'HostedZoneId' => str_replace('/hostedzone/', '', $service['Id'] ), // REQUIRED
                #'StartRecordName' => 'NS',
            ]);

            $rrss = $result['ResourceRecordSets'];
            
            foreach($rrss as $rrs){
                if( $rrs['Type'] == 'NS' )
                    return $rrs['ResourceRecords'];
            }
            return false;
        }
        


        public function getServiceById( $id ){
                #echo PHP_EOL . "\$id = $id " . PHP_EOL; 
                if( $id != true)
                    return false;

                $client = $this->getClientR53();

                $resultHzs = $client->listHostedZones([]);
                $hzs = $resultHzs['HostedZones'];
                #var_export ( $hzs ); die();
                for($i=0; $i < count( $hzs ); $i++ ){
                    $hz = $hzs[$i];
                    #echo PHP_EOL . "\$hz['Id'] = " . $hz['Id'] . " \$id = $id" . PHP_EOL; 
                    if( strpos( $hz['Id'], $id )  !== FALSE ) 
                        return $hz;
                }

                return false;

        }

        
        /**
         * Esta función devuelve todos los elementos de un servicio cloud con un nombre determinado.
         * El objeto VPC carece del parámetro VpcName, por lo que busca en los campos Tag 
         * 
         * @param string $id [vpc-]...
         * @return array Objeto de AWS
         */
        public function getServiceByName( $name  ){
            if( $name != true)
                return false;
            
            
            $client     = $this->getClientR53();
            $services   = $client->listHostedZones();
            $services   = $services['HostedZones'];
            foreach( $services as $service )
                if(  preg_match("/$name/i", $service['Name'])  )
                    return $service;
            
            return false;

        }


        public function getServiceIdByName( $name ){

            $service = $this->getServiceByName( $name );
            if ( $service == false )
                return false;

            return str_replace('/hostedzone/', '', $service['Id'] );

        }


        /**
         * Esta función devuelve todos los elementos de un servicio cloud con un nombre determinado.
         * El objeto VPC carece del parámetro VpcName, por lo que busca en los campos Tag.
         * Esta función es idéntica a getServiceByName y se ha implementado por tema de compatibilidad
         * 
         * @param string $id [vpc-]...
         * @return array Objeto de AWS
         * */

        public function getServiceByTagName( $name = null ){
            return $this->getServiceByName( $name );
        }


        public function delete( $id){
            if( $id != true)
                return false;

            $client = $this->getClientR53();
            $result = $client->deleteHostedZone([
                #'DryRun' => true || false,
                'Id' => $id, // REQUIRED
            ]);

            $this->waitToBeDeleted( $id );

        }

        /**
         * @param string $name    Nombre de HostedZone
         * @param string $id      Una id de HostedZone. Usualmente comienza por "Z"
         * 
         *
         * @example 
         * <code>
         * <?php
         *  
         *  OK - In this sentence the function search matching param $id
         *  $object->exists( $name = null, $id  = "Zxxxxxxxx" ){ ... }
         *   
         *  OK - In this sentence the function search matching param $name
         *  $object->exists( $name = HZ_NAME, $id  = null){ ... }
         * 
         *  OK - In this sentence the function search matching both params
         *  $object->exists( $name = HZ_NAME, $id  = "Zxxxxxxxx" ){ ... }
         *
         * ?>
         * </code>
         * 
         */


        public function existsById( $id ){
            if($id != true)
                return false;
            $service = $this->getServiceById( $id );
            if( $service == false)
                return false;

            return true;

        }

        public function existsByName( $name ){
            if($name != true)
                return false;
            
            $client     = $this->getClientR53();
            $services   = $client->listHostedZones();
            $services   = $services['HostedZones'];
            foreach( $services as $service ){
                if( preg_match("/^$name/i", $service['Name'] )  ) {
                    return true;
                }
                    

            }
            return false;

        }


        public function isAvailableById( $id ){
            if( $id != true)
                return false;
            return $this->existsById($id);
        }
        
        /**
         * @param string $vpcId         Una id de vpc. Usualmente comienza por "vpc-"
         * @param string $vpcTagName    valoer del tag "Name"
         *
         * @return boolean
         * 
         */

        public function isAvailableByName( $name ){
            if( $name != true)
                $name = "";
            return $this->existsByName ($name);
        }


        /**
         * @param string    $name    valoer del tag "Name"
         * @param int       $seconds    Tiempo que duerme el itinerador
         * @param int       $times      Número máximo de comprobaciones
         * 
         */

        public function waitToBeCreated( $id, $seconds = null, $times = null ){
            if ($seconds == null)
                $seconds = 5;

            if ($times == null)
                $times = 12;


            for($i=0, $isCreated = false; $i < $times; $i++ ) {
                sleep( $seconds );
                if( $this->isAvailableById( $id ) )
                    return true;
            }

            return false;
                
        }

        public function waitToBeDeleted( $name, $sleeper = null, $times = null ){
            return true; 
                
        }



        /**
         * Consigue la id dando el nombre como parámetro.
         * 
         * La id de un hostedzone es un caso curioso pues tiene este formato:
         *  /hostedzone/Z[string]       
         * 
         * Pero en la mayoría de los lugares que se precisa la id de un hostedzone se requiere Z[string]
         * 
         * @param string $name N ombre del hostedzone a buscar
         * @return string Z[string] . Se eliminia la cadena '/hostedzone/'           
         * 
         * */


        public function getId() {
            return $this->id;
        }

        public function getIdByName( $name ) {
            if( $name != true)
                return false;

            $service = $this->getServiceByName($name);
            if( $service != false)
                return str_replace('/hostedzone/', '', $service['Id'] );

            return false;
        }


        public function setId( $id ) {
            $this->id = str_replace('/hostedzone/', '', $id );
        }

        
        public function getStatusById ($param ){
            return true;
        }

        public function getStatusByName ($param ){
            return true;
        }

    }

?>
