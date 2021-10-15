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
        
        
        private $fields = [
            'CallerReference',              #string 
            'Config',                       #array[Comment:string, PrivateZone:booleasn]
            'Id',                           #string:/hostedzone/Z[string]
            'LinkedService',                #array[Description:string, ServicePrincipal:string]
            'Name',                         #string
            'ResourceRecordSetCount',       #int
        ];
        


        public function __construct(){
            $this->setCredentials();
            $this->setClientR53();
            $this->setClientEc2();
            
        }
        
        /**
         * Esta función crea un objeto HostedZone de AWS.
         * Si existe carga los datos del existente en el objeto
         */

        public function create() {
            #$client = CDH_abstractAwsService::getClientEc2();
            $clientR53 = $this->getClientR53();
            $clientEc2 = $this->getClientEc2();

            
            $vpc = new CDH_awsVpc();
            $vpcId = $vpc->getId( CDH_VPC_NAME );
            
            try{

                if ($this->exists( CDH_HZ_Name )) {        
                    $msg = "OK;HOSTEDZONE;002;49";
                    throw new Exception( $msg );
                }

                $resultR53 = $clientR53->createHostedZone([
                    'CallerReference' => strval( time() ), // REQUIRED
                    #'DelegationSetId' => '<string>',
                    'HostedZoneConfig' => [
                        'Comment' => CDH_HZ_HostedZoneConfig_Comment,
                        #'PrivateZone' => true || false,
                    ],
                    'Name' => CDH_HZ_Name, // REQUIRED
                    'VPC' => [
                        'VPCId' => $vpcId,
                        'VPCRegion' => CDH_AWS_REGION,
                    ],
                ]);
                    
                if( $this->waitToBeCreated( CDH_HZ_Name) == false ){
                    $msg = "KO;HOSTEDZONE;002;68";
                    return $this->createResult($msg);
                }
                    
                $this->setCloudObject($resultR53['HostedZone']);
                $this->setId($resultR53['HostedZone']['Id']); 
                

            }catch (Exception $e) {
                
                $resultHzs = $clientR53->listHostedZones([]);
                $hzs = $resultHzs['HostedZones'];
                foreach($hzs as $hz){
                    if( strcmp($hz['Name'], CDH_HZ_Name ) == 0 )
                        break;

                }

                $this->setCloudObject( $hz );
                $this->setId( $hz['Id'] );
                return $this->createResult( $e->getMessage() );
            }
               

            try{

                $result = $clientR53->changeTagsForResource([
                    'AddTags' => [
                        [
                            'Key' => CDH_HZ_TAGS_Name,
                            'Value' => CDH_HZ_TAGS_Value,
                        ],
                        // ...
                    ],
                    #'RemoveTagKeys' => ['<string>', ...],
                    'ResourceId' => $this->getId(), // REQUIRED
                    'ResourceType' => 'hostedzone', // REQUIRED
                ]);


            }catch (AwsException $e) { //Suponemos que si no se crea es porque ya existe
                echo $e->getMessage();
                return false;
            }
            #echo PHP_EOL . "\$this->getId() = " . $this->getId() . PHP_EOL;
            $this->setCloudObject( $this->getServiceById( $this->getId() ) );

            $msg = "OK;HOSTEDZONE;001;49";
            return $this->createResult($msg);

            /**
             * // -- crear mensajes de exito error clase error crear
             */

        }

        /**
         * Esta función carece de sentido, pues los únicos valores interesantes son el Nombre y la Id y ambos los tenemos
         *  
         * */
        public function getFieldValueById ( $field, $id ) {
            return true;    

        }

        /**
         * Esta función busca el valor de un campo del array. Existen campos con igual nombre
         * pero en distinto nivel del array. si el campo buscado está en el primer nivel o es único
         * la función trabaja bien.
         * 
         * En los casos en que el campo esté repetido, pero en distinto nivel, es necesario insertar 
         * el argumento $cloudObject como un array interno con campos únicos   
         * 
         * @param string $field cualquier valor contenido en el HostedZone - Realmente sólo puede captar la Id
         *                  
         * 'Id'                          (cadena)
         * 
         * @param string $hzName - Nombre de un hostedzone
         * 
         * @return string|array|boolean
         * 
         * */

        public function getFieldValueByName ( $field, $name ){

            $fields = array("Id");

            $i = in_array_ic ($field, $fields);
            if( $i == false ) 
                return false;
            
            $field = $fields[$i];

            $resultHzs = $clientR53->listHostedZones([]);
            $hzs = $resultHzs['HostedZones'];

            for( $i=0, $existeField=false; $i < count( $hzs ); $i++ ){ 
                $hz = $hzs[$i];
                if( strcmp($hz['Name'], $name ) == 0 )
                    return $hz[$field];
            }

            return false;

        }

        #public function getTagValue( $tagKey, $vpcName = false, $vpcId = false ) {
        public function getTagValue( $tagKey,  $hzName = null,  $hzId = null ) {
        
            if( $hzId == null && $hzName == null){ //Se trata del vpc creado con el mismo objeto
                $hz = $this->getCloudObject();
                #$hzTags = $hz['Tags'];
            }
            
            if( $hzId == null && $hzName == true){
                $hz = $this->getServiceByName( $hzName );                
                #$hzTags = $hz['Tags'];
            }


            if( $hzId == true && $hzName == null ){ //Si $id == $name == true siemre se busca por $id
                $hz = $this->getServiceById( $hzId );
                #$hzTags = $hz['Tags'];
            }
            
            if( $hzId == true && $hzName == true ){
                $hz    = $this->getServiceByName ( $hzName  );
                $hz0   = $this->getServiceById   ( $hzId    );
                if (array_diff($hz,$hz0) != array_diff($hz0,$hz)) 
                    return false;
                #$hzTags = $hz['Tags'];
            }

            $hzTags = $hz['Tags'];
            foreach( $hzTags as $tag  ) 
                if( $tag['Key'] == $tagKey )
                    return $tag['Value'];

            return false;



        }

        /**
         * Esta función devuelve todos los elementos de un servicio cloud de una id determinada
         * 
         * @param string $id [vpc-]...
         * @return array Objeto de AWS
         */


        public function getServiceById( $id ){
                #echo PHP_EOL . "\$id = $id " . PHP_EOL; 
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

            $client = $this->getClientR53();

            $resultHzs = $client->listHostedZones([]);
            $hzs = $resultHzs['HostedZones'];
            for($i=0, $existeHz = false; $i < count( $hzs ); $i++ ){
                $hz = $hzs[$i];
                if( strpos( $hz['Name'], $name )  !== FALSE ) 
                        return $hz;
            }
            return false;
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


        public function delete( $name = null){
            $id = $this->getId( $name );            
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


        public function exists( $name = null, $id  = null ){
            try{

                if( $name == true && $id == true ){                    
                    $hz    = $this->getServiceByName ( $name  );
                    $hz0   = $this->getServiceById   ( $id    );
                    if (array_diff($hz,$hz0) != array_diff($hz0,$hz)) 
                        throw new Exception("Datos incongruentes 0");
                    return true;    
                }
                
                if( $name == true  ){                    
                    $hz    = $this->getServiceByName ( $name  );
                    if( isset($hz) && is_array( $hz ) && count( $hz ) > 0  )
                        return true;
                    throw new Exception("Datos incongruentes 1");        
                }

                if( $id == true  ){                    
                    $hz    = $this->getServiceById ( $id  );
                    if( isset($hz) && is_array( $hz ) && count( $hz ) > 0  )
                        return true;
                    throw new Exception("Datos incongruentes 2");        
                }

                return false;
            }catch ( Exception $e ) {
                return false;
            }

        }

        
        /**
         * @param string $vpcId         Una id de vpc. Usualmente comienza por "vpc-"
         * @param string $vpcTagName    valoer del tag "Name"
         *
         * @return boolean
         * 
         */

        public function isAvailable( $name  = null, $id = null ){
            return $this->exists($name, $id);
        }


        /**
         * @param string    $name    valoer del tag "Name"
         * @param int       $seconds    Tiempo que duerme el itinerador
         * @param int       $times      Número máximo de comprobaciones
         * 
         */

        public function waitToBeCreated( $name, $seconds = null, $times = null ){
            if ($seconds == null)
                $seconds = 5;

            if ($times == null)
                $times = 12;


            for($i=0, $isCreated = false; $i < $times; $i++ ) {
                sleep( $seconds );
                if( $this->isAvailable( $name ) )
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

        public function getId( $name = null ) {
            if( $name == null)
                return $this->id;

            $hz = $this->getServiceByName($name);
            if( $hz != false)
                return str_replace('/hostedzone/', '', $hz['Id'] );

            return false;

        }

        public function setId( $id ) {
            $this->id = str_replace('/hostedzone/', '', $id );
        }

        public function getFields(){
            return $this->fields;
        }

        public function getStatus ($param ){
            return true;
        }


    }

?>
