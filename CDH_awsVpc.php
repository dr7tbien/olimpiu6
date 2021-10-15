<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    require_once 'classes/CDH_abstractAwsService.php';
    require_once 'defines/services_aws/CDH_awsDefineVpc.php';
    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;

    class CDH_awsVpc extends CDH_abstractAwsService {

        


        public function __construct(){
            $this->setCredentials();
            $this->setClientEc2();
            $this->setClassName("CDH_awsVpc");
        }
        
        public function create ( $data = null ) {

            $client = $this->getClientEc2();
            #$id     = $data['CacheClusterId'];
            $name   =  $data['TagSpecifications'][0]['Tags'][0]['Value'];

            try{
                if ( isset($id) && $this->existsById( $id ) )
                    throw new Exception( "L044 El servicio " . $this->getClassName() . " con la id " . $id . " ya existe");
                
                if ( isset($name) && $this->existsByName( $name ) )
                    throw new Exception( "L047 El servicio " . $this->getClassName() . " con el nombre " . $name . " ya existe");
               
                $result     = $client->createVpc( $data );
                $identifier = $result['Vpc']['VpcId'];
    
                if( $this->waitToBeCreated(  $identifier  ) == false )
                    throw new Exception( "L054 Demasiado tiempo esperando a la creación del servicio " . $this->getClassName() );
                $this->logAdmin(  "L042 OK  " . $this->getClassName() . "->create()" );
                return true ;
            
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



        public function createOld( $data = []) {

            $name = $data['TagSpecifications'][0]['Tags'][0]['Value'];
            #die( "\$name = $name\n"); 

            if ($this->existsByName( $name )) {        
                $msg = "OK;VPC;002;52";
                die( "\nL042  VPC $name existe \n" );
            }

            $client = $this->getClientEc2();

            try{

                $result = $client->createVpc ( $data );
                                
                if( $this->waitToBeCreated(  $result['Vpc']['VpcId'] ) == false ) {
                    $msg = "KO;VPC;002:66";
                    return false;
                }

            }catch (AwsException $e) {
                
                echo $e->getMessage();                        
                return false;
            }

            try{

                $client->modifyVpcAttribute([
                    'EnableDnsHostnames' => [
                        'Value' => true,
                    ],
                    'VpcId' => $result['Vpc']['VpcId'],
                ]);
            }catch (AwsException $e) { //Suponemos que si no se crea es porque ya existe
                echo $e->getMessage();
                return false;
            }

            return $result['Vpc'];

        }

        /**
         * Esta función busca el valor de un campo del array. Existen campos con igual nombre
         * pero en distinto nivel del array. si el campo buscado está en el primer nivel o es único
         * la función trabaja bien.
         * 
         * En los casos en que el campo esté repetido, pero en distinto nivel, es necesario insertar 
         * el argumento $cloudObject como un array interno con campos únicos   
         * 
         * @param string $field cualquier valor contenido en el VPC
         *                  
         * 'CidrBlock'                      (cadena)
         * 'CidrBlockAssociationSet'        (array)           
         * 'DhcpOptionsId'                  (cadena)
         * 'InstanceTenancy'                (cadena: default|dedicated|host)
         * 'Ipv6CidrBlockAssociationSet'    (array)
         * 'IsDefault'                      (boolean)
         * 'OwnerId'                        (cadena)
         * 'State'                          (cadena: pending|available)
         * 'Tags'                           (array)
         * 'VpcId'                          (cadena)
         * 
         * @param string $vpcId
         * 
         * @return string|array|boolean
         * 
         * */


        public function getFieldValueById ( $field, $vpcId ) {
            $fields = $this->getFields();

            for( $i=0, $existeField = false; $i < count( $fields ); $i++ ) {
                if( strcasecmp( $fields[$i], $field ) == 0 ){
                    $field = $fields[$i];
                    $existeField = true;
                    break;
                }
            }

            $clientEc2 = $this->getClientEc2();
            $result = $clientEc2->describeVpcs([
                #'DryRun' => true || false,
                #'Filters' => [
                #    [
                #        'Name' => '<string>',
                #        'Values' => ['<string>', ...],
                #    ],
                    // ...
                #],
                #'MaxR esults' => <integer>,
                #'NextToken' => '<string>',
                'VpcIds' => [$vpcId],
            ]); 

            $vpcs = $result->search('Vpcs');

            if ( count( $vpcs ) != 1)
                return false;

            $vpc = $vpcs[0];

            return $vpc[ $field ];

        }

        /**
         * Esta función busca el valor de un campo del array. Existen campos con igual nombre
         * pero en distinto nivel del array. si el campo buscado está en el primer nivel o es único
         * la función trabaja bien.
         * 
         * En los casos en que el campo esté repetido, pero en distinto nivel, es necesario insertar 
         * el argumento $cloudObject como un array interno con campos únicos   
         * 
         * @param string $field cualquier valor contenido en el VPC
         *                  
         * 'CidrBlock'                      (cadena)
         * 'CidrBlockAssociationSet'        (array)           
         * 'DhcpOptionsId'                  (cadena)
         * 'InstanceTenancy'                (cadena: default|dedicated|host)
         * 'Ipv6CidrBlockAssociationSet'    (array)
         * 'IsDefault'                      (boolean)
         * 'OwnerId'                        (cadena)
         * 'State'                          (cadena: pending|available)
         * 'Tags'                           (array)
         * 'VpcId'                          (cadena)
         * 
         * @param string $vpcName
         * 
         * @return string|array|boolean
         * 
         * */

        public function getFieldValueByName ( $field, $vpcName ){

            $fields = $this->getFields();

            for( $i=0, $existeField=false; $i < count( $fields ); $i++ ) 
                if( strcasecmp( $fields[$i], $field ) == 0 )
                    $existeField = true;
            
            if( $existeField == false ) return false;        

            $clientEc2 = $this->getClientEc2();
            $result = $clientEc2->describeVpcs([
                'Filters' => [
                    [
                        'Name' => 'tag:Name',
                        'Values' => [$vpcName],
                    ],
                        // ...
                ],
            ]); 

            $vpcs = $result->search('Vpcs');

            if ( count( $vpcs ) != 1)
                return false;

            $vpc = $vpcs[0];

            return $vpc[ $field ];

        }


        public function getTagValueByName( $tagKey, $name) {
            if( $tagKey != true || $name != true )
                return false;
            $service = $this->getServiceByName( $name );
            if(!is_array ($service) )
                return false;
            $tags = $service['Tags'];
            foreach( $tags as $tag ) {
                if( strcmp ($tag['Key'], $tagKey)  == 0)
                    return $tag['Value'];
            }
            return false;
        }


        public function getTagValueById( $tagKey, $id ) {
            if( $tagKey != true || $id != true )
                return false;
            $service = $this->getServiceById( $id );
            if(!is_array ($service) )
                return false;
            $tags = $service['Tags'];
            foreach( $tags as $tag ) {
                if( strcmp ($tag['Key'], $tagKey)  == 0)
                    return $tag['Value'];
            }
            return false;
        }




        /**
         * Esta función devuelve todos los elementos de un servicio cloud de una id determinada
         * 
         * @param string $id [vpc-]...
         * @return array Objeto de AWS
         */


        public function getServiceById( $id ){
            if($id != true) $id = "";
            try{
                $client = $this->getClientEc2();
                $result = $client->describeVpcs([ 'VpcIds' => [ $id ] ]);
                if( count ( $result['Vpcs']) != 1)
                    throw new Exception();
                return $result['Vpcs'][0];
            }catch ( Exception $e ) {
                return false;
            }

        }

        /**
         * Esta función devuelve todos los elementos de un servicio cloud con un nombre determinado.
         * El objeto VPC carece del parámetro VpcName, por lo que busca en los campos Tag 
         * 
         * @param string $id [vpc-]...
         * @return array Objeto de AWS
         * */

        public function getServiceByName( $name  ){
            //die($name);
            if($name != true) $name = "";

            try{
                $clientEc2 = $this->getClientEc2();
                $result = $clientEc2->describeVpcs([
                    'Filters' => [
                        [
                            'Name' => 'tag:Name',
                            'Values' => [$name],
                        ],
                            // ...
                    ],
                ]);
                if( count ( $result['Vpcs']) != 1)
                    throw new Exception();
                return $result['Vpcs'][0];

            } catch ( AwsException $e ) {
                return false;
            }

            #if( is_array( $result['Vpcs'] )  && count( $result['Vpcs'] ) == 1 )
            #    return $result['Vpcs'][0];
                
            #return false;
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
            $id = $this->getIdByName( $name );            
            $client = $this->getClientEc2();
            $result = $client->deleteVpc([
                #'DryRun' => true || false,
                'VpcId' => $id, // REQUIRED
            ]);

        }

        /**
         * @param string $vpcId         Una id de vpc. Usualmente comienza por "vpc-"
         * @param string $vpcTagName    valoer del tag "Name"
         *
         * @example 
         * <code>
         * <?php
         *  
         *  OK - In this sentence the function search matching param $vpcId
         *  $object->exists( $vpcId  = "vpc-xxxxxxxx", $vpcTagName = false ){ ... }
         *   
         *  OK - In this sentence the function search matching param $vpcTagName
         *  $object->exists( $vpcId  = false, $vpcTagName = "VPC_NAME" ){ ... }
         * 
         *  OK - In this sentence the function search matching both params
         *  $object->exists( $vpcId  = "vpc-xxxxxxxx", $vpcTagName = "VPC_NAME" ){ ... }
         *
         * ?>
         * </code>
         * 
         */




        public function existsById( $id ){
            $service = $this->getServiceById( $id );
            if( $service == false)
                return false;
            return true;
        }

        public function existsByName( $name ){
            $client = $this->getClientEc2();
            #$name = "CD_sndnv_vpc";
            try{
                $result = $client->describeVpcs([
                    'Filters' => [
                        [
                            'Name' => 'tag:Name',
                            'Values' => [$name],
                        ],
                            // ...
                    ],
                ]);

            } catch ( AwsException $e ) {
                return false;
            }


            if( isset ($result['Vpcs']) && is_array($result['Vpcs']) && count ( $result['Vpcs']) != 1)
                return false;

            if( isset ($result['Vpcs']) && is_array($result['Vpcs']) && count ( $result['Vpcs']) == 1)
                return true;

            return false;


        }

        public function exists( $id ){
            return $this->existsById( $id );
        }


        public function isAvailableById( $id ){
            if( $id != true )
                return false;

            $service = $this->getServiceById($id);
            if($service == false)
                return false;
            
            if( is_array( $service ) && isset($service['State']) && strcmp( $service['State'], 'available'  ) == 0 )
                return true;
            return false;
        }
        
        /**
         * @param string $vpcId         Una id de vpc. Usualmente comienza por "vpc-"
         * @param string $vpcTagName    valoer del tag "Name"
         *
         * @return boolean
         * 
         */

        public function isAvailableByName( $name ){
            if($name != true)
                return false;

            $service = $this->getServiceByName($name);
            if($service == false)
                return false;
            
            if( is_array( $service ) && isset($service['State']) && strcmp( $service['State'], 'available'  ) == 0 )
                return true;
            return false;
        }
        
        /**
         * @param string $vpcId         Una id de vpc. Usualmente comienza por "vpc-"
         * @param string $vpcTagName    valoer del tag "Name"
         *
         * @return boolean
         * 
         */

        public function isAvailable( $id  = false, $vpcTagName = false ){
            $clientEc2 = $this->getClientEc2();

            if( $id == false && $vpcTagName == false )
                return false;

            try{

                if( ($id == true && $vpcTagName == true) || $vpcTagName == true ){
                    $result = $clientEc2->describeVpcs([
                        'VpcIds' => [$id],
                        'Filters' => [
                            [
                                'Name' => 'tag:Name',
                                'Values' => [$vpcTagName],
                            ],
                                // ...
                        ],
                    ]);
        

                }        
                

                if( $id == true && $vpcTagName == false){
                    $result = $clientEc2->describeVpcs([
                        'VpcIds' => [$id],
                    ]);
                }

                if( (count( $result['Vpcs'] ) == 1) && $result['Vpcs'][0]['State'] == 'available' )
                    return true;
    
                return false;

            }catch ( AwsException $e ) {
                return false;
            }

        }


        /**
         * @param string    $vpcName    valoer del tag "Name"
         * @param int       $sleeper    Tiempo que duerme el itinerador
         * @param int       $times      Número máximo de comprobaciones
         * 
         */

        public function waitToBeCreated( $id, $seconds = null, $times = null){
            if ($seconds == null)
                $seconds = 5;

            if ($times == null)
                $times = 12;


            for($i=0, $isCreated = false; $i < $times; $i++ ) {
                sleep( $seconds );
                if( $this->isAvailableById ( $id ) )
                    return true;
            }

            return false;

                
        }

        public function waitToBeDeleted( $vpcName, $sleeper = false, $times = false){
            return true;
                
        }


        public function getId() {
                return $this->id;
        }

        public function getIdByName( $name ) {
            #echo "\n\$name = $name";
            if($name != true )
                return false;
            
            $service = $this->getServiceByName( $name );
            #print_r($service);
            #echo "\n\$service['VpcId'] = " .  $service['VpcId']  ."\n"; 
            if( $service )
                return $service['VpcId'];
            
            return false;
        }


        public function setId( $id ) {
            $this->id = $id;
        }

        public function getFields(){
            return $this->fields;
        }

        public function getStatusById ($id ){
            if( $id != true )
                return false;
            $service = $this->getServiceById($id);
            if ($service == false)
                return false;
            if( is_array( $service) && isset(  $service['State'] ) )
                return $service['State'];


            return false;
        }

        public function getStatusByName ($name ){
            if( $name != true )
                return false;

            $service = $this->getServiceByName($name);
            if ($service == false)
                return false;
            if( is_array( $service) && isset(  $service['State'] ) )
                return $service['State'];

            return false;
        }
    }

?>
