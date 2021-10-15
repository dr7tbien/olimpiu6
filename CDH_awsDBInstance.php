<?php
    
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0

    */
    
    require_once 'classes/CDH_abstractAwsService.php';  
    require_once 'classes/CDH_awsVpc.php';        
    require_once 'classes/CDH_awsSecurityGroup.php';        
    require_once 'classes/CDH_awsSubnet.php';
    require_once 'classes/CDH_awsDBClusterParameterGroup.php'; 
    require_once 'classes/CDH_awsDBParameterGroup.php'; 
    require_once 'classes/CDH_awsDBSubnetGroup.php'; 
    require_once 'classes/CDH_awsOptionGroup.php'; 
    require_once 'classes/CDH_awsDBCluster.php'; 
    
    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    
    class CDH_awsDBInstance extends CDH_abstractAwsService {
        
        public function __construct(){

            $this->setCredentials();
            $this->setClientEc2();
            $this->setClientRds();
            $this->setClientEcc();
            $this->setClassName("CDH_awsDBInstance");

        }
        

        public function create ( $data = [] ) {

            $client = $this->getClientRds();
            $id     = $data['DBInstanceIdentifier'];
            #$name = $data['DBParameterGroupName'];

            try{
                if ( $this->existsById( $id ) )
                    throw new Exception( "L044 El servicio " . $this->getClassName() . " con la id " . $id . " ya existe");
                
                $result     = $client->createDBInstance( $data );
                $service    = $result  ['DBInstance'];
                $id         = $service ['DBInstanceIdentifier'];
                if( $this->waitToBeCreated(  $id  ) == false )
                    throw new Exception( "L050 Demasiado tiempo esperando a la creación del servicio " . $this->getClassName() );
                
                return true;
            
            }catch (AwsException $e) {
                $this->logAdmin(  "L055 Error  " . $this->getClassName() . "->create()" );  
                $this->logAdmin(  "\n Mensaje de error:" );
                $this->logAdmin(  $e->getMessage() );
                return false;
            }catch(Exception $e){
                $this->logAdmin(  "L060 Error  " . $this->getClassName() . "->create()" );  
                $this->logAdmin(  "\n Mensaje de error:" );
                $this->logAdmin(  $e->getMessage() );
                return false;
            }

        }

        /**
         * Esta función carece de sentido, pues los únicos valores interesantes son el Nombre y la Id y ambos los tenemos
         *  
         * */

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
         * @param string $name - Nombre de la Subnet
         * 
         * @return string|array|boolean
         * 
         * */


        public function getFieldValueByName ( $field, $name ){

            if( $field != true || $name != true )
                return false;

            $fields = $this->getFields();
            $i = $this->in_array_ic ($field, $fields);
            if( $i == false ) 
                return false;
            
            $field = $fields[$i];
            $service = $this->getServiceByName($name);
            if ($service == false)
                return false;
            return $service[$field];


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
                if(  isset( $tag['Key'] ) && strcmp ($tag['Key'], $tagKey)  == 0 )
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
            if($id != true)
                return false;
            $client = $this->getClientRds();
            try{
                $result = $client->describeDBInstances([
                    'Filters' => [
                        [
                            'Name' => 'db-instance-id', // REQUIRED
                            'Values' => [$id],          // REQUIRED
                        ],
                    ],
                ]);
            }catch( AwsException $e){
                return false;
            }

            if( !is_object($result ) || !is_array( $result ['DBInstances'] ) || count( $result['DBInstances'] ) != 1   )
                return false;

            $services = $result['DBInstances'];
            
            return $services[0];
        }

        
        /**
         * Esta función devuelve todos los elementos de un servicio cloud con un nombre determinado.
         * El objeto VPC carece del parámetro VpcName, por lo que busca en los campos Tag 
         * 
         * @param string $id [vpc-]...
         * @return array Objeto de AWS
         */

        public function getServiceByName( $name  ){
            
            return $this->getServiceById( $name );
            

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

            if($name != true)
                return false;

            $client = $this->getClientRds();
            
            try{
                $result = $client->describeDBInstances([
                    'Filters' => [
                        [
                            'Name' => 'tag:Name',
                            'Values' => [$name],
                        ]
                    ] 
                ]);
        
            }catch( AwsException $e){
                
                return false;
            }

            if( !is_object($result ) || !is_array( $result ['DBInstances'] )    )
                return false;

            $services = $result['DBInstances'];
            
            foreach( $services as $service ){
                $tags = $service['Tags'];
                foreach( $tags as $tag )
                    if( $tag['Key'] == 'Name' && $tag['Value'] == $name)
                        return $service;
                
            }
            
            
            return false;

        }


        public function delete( $id ){
            if( !$this->exists($id) )
                return true;
            
            try{
                $client = $this->getClientRds();

                $result = $client->deleteDBInstance([
                    'DBInstanceIdentifier' => $id, // REQUIRED
                ]);

            }catch(AwsException $e){
                echo $e->getMessage();
                return false;

            }

            return $this->waitToBeDeleted( $id );


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


        public function exists( $id ){
            return $this->existsById( $id );
        }

        public function existsById( $id ){
            if( $id != true)
                return false;

            $service = $this->getServiceById( $id );
            if( $service == false)
                return false;

            return true;

        }


        public function existsByName( $name ){
            if( $name != true)
                return false;
            
            $service = $this->getServiceByName( $name );

            if( $service != false && is_array ( $service ) )
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

        public function isAvailableById( $id ){
            
            $service = $this->getServiceById( $id );
            #$status = $service['DBInstanceStatus'];
            if( isset( $service['DBInstanceStatus'] ) && strcmp( $service['DBInstanceStatus'], 'available' ) == 0 )
                return true;
            
            return false;
            
            /*    
            if( strcmp( $service['State'], 'pending' ) == 0 )
                return false;   

            if( strcmp( $service['State'], 'available' ) == 0 )
                return true;
                
            return false;
            */
        }

        /**
         * @param string $vpcId         Una id de vpc. Usualmente comienza por "vpc-"
         * @param string $vpcTagName    valoer del tag "Name"
         *
         * @return boolean
         * 
         */

        public function isAvailableByName( $name ){
            
            $service = $this->getServiceByName( $name );
            if( isset( $service['DBInstanceStatus'] ) && strcmp( $service['DBInstanceStatus'], 'available' ) == 0 )
                return true;
            
            return false;


            #if( $service == false )
            #    return false;
            
            #return true;


            /*
            if( $service == false )
                return false;
            if( strcmp( $service['State'], 'pending' ) == 0 )
                return false;   

            if( strcmp( $service['State'], 'available' ) == 0 )
                return true;
                
            return false;
            */
        }


        /**
         * @param string    $name    valoer del tag "Name"
         * @param int       $seconds    Tiempo que duerme el itinerador
         * @param int       $times      Número máximo de comprobaciones
         * 
         */

        public function waitToBeCreated( $id, $seconds = null, $times = null ){
            if($seconds != true)
                $seconds = 50;
        
            if($times != true)
                $times = 30;

            $client = $this->getClientRds();

            for($i = 0; $i < $times;  $i++, sleep($seconds) ){
                $result = $client->describeDBInstances([
                    'DBInstanceIdentifier' => $id,
                ]);
                
                if( isset( $result['DBInstances'][0]['DBInstanceStatus'] ) )
                    $status = $result['DBInstances'][0]['DBInstanceStatus'];
                
                if( isset( $status ) && strcasecmp($status, 'available') == 0 ) 
                    return true;
            
                                   
            }

            return false;
        }

        public function waitToBeDeleted( $id, $seconds = null, $times = null ){
            if($seconds != true)
                $seconds = 30;
    
            if($times != true)
                $times = 6;
            
            for($i = 0; $i < $times;  $i++ ){
                $service = $this->getServiceById( $id );
                if( $service == false  )
                    return true;                
                sleep($seconds);
            }

            return false;
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
            return false; 

            $service = $this->getServiceByName( $name );
            if( isset( $service[ 'DBInstanceIdentifier' ] ) )
                return $service[ 'DBInstanceIdentifier' ];
            
            return false;
        }

        public function setId( $id ) {
            $this->id = $id;
        }

        public function getFields(){
            return $this->fields;
        }

        public function getStatus ($id ){
            
            $service = $this->getServiceById( $id );
            
            if ( isset( $service['DBInstanceStatus'] )  )
                return $service['DBInstanceStatus'];
            return false;
        
        }


        public function getStatusById ($id ){
            #return true;
            return $this->getStatus( $id );
        }


        public function getStatusByName ($name ){
            #return true;
            $service = $this->getServiceByName( $name );
            
            return   $service[ 'DBInstanceStatus' ] ;



            #if ( $service == false)
            #    return false;

            #return $service['State'];

        }




    }

?>
