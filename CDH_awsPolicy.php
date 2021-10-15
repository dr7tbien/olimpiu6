<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    require_once 'classes/CDH_abstractAwsService.php';

    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    class CDH_awsPolicy extends CDH_abstractAwsService {   
        
        


        public function __construct(){
            $this->setCredentials();
            $this->setClientIam();
            $this->setClassName("CDH_awsPolicy");
        }
        
        public function create ( $data = null) {

            $client = $this->getClientIam();
            #$id     = $data['DBInstanceIdentifier'];
            $name   = $data['PolicyName'];
            
            try{
                if ( isset($id) && $this->existsById( $id ) )
                    throw new Exception( "L044 El servicio " . $this->getClassName() . " con la id " . $id . " ya existe");
                
                if ( isset($name) && $this->existsByName( $name ) )
                    throw new Exception( "L047 El servicio " . $this->getClassName() . " con el nombre " . $name . " ya existe");
                
                $result     = $client->createPolicy( $data );
                $identifier = $result['OptionGroup']['OptionGroupName'];
                if( $this->waitToBeCreated(  $identifier  ) == false )
                    throw new Exception( "L054 Demasiado tiempo esperando a la creación del servicio " . $this->getClassName() );
                sleep(10);
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

        


        public function createOld( $data = null ) {
            
            
            if ( $this->existsByName( $data['PolicyName'] ) ){        
                return true;
            }

            $client = $this->getClientIam();

            try{
                $result = $client->createPolicy( $data );
                /*
                $result = $client->createPolicy([
                    'Description'       => $data['Description'],
                    'PolicyDocument'    => file_get_contents( $data['PolicyDocument'] ) , // REQUIRED     policy.json
                    'PolicyName'        => $data['PolicyName'],                           // REQUIRED
                ]);
                */
                    /*
                if( $this->waitToBeCreated(  $data['PolicyName']  ) == false ){
                    $msg = "KO;IAMPOLICY;002;99";
                    throw new Exception( $msg );
                }
                      */              
            }catch (AwsException $e) {
                $e->getMessage();
                return false;
            }catch(Exception $e){
                $e->getMessage();
                return false;
            }
                        
            return $result['Policy'];

        }

        
        public function getArnByName( $name ){

            $client = $this->getClientIam();

            $result = $client->listPolicies([]);

            $policies = $result['Policies'];

            foreach ( $policies as $policy ) {
                echo "\n L125 policy[PolicyName] = " . $policy['PolicyName'];
                echo "\n L125 NAME = " .$name;
                if( strcmp( $policy['PolicyName'], $name ) == 0  ) 
                    return  $policy['Arn'];
            }
            return false;

        }

        /**
         * Esta función carece de sentido, pues los únicos valores interesantes son el Nombre y la Id y ambos los tenemos
         *  
         * */
        public function getFieldValueById ( $field, $id ) {
            
            if( $field != true || $id != true )
                return false;

            $fields = $this->getFields();
            $service = $this->getServiceById( $id );

            if ( in_array( $field, $fields ) && $service != false ) 
                return $service[$field];

            return false;               

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
         * @param string $name - Nombre de la Subnet
         * 
         * @return string|array|boolean
         * 
         * */


        public function getFieldValueByName ( $field, $name ){
            

            if( $field != true || $name != true )
                return false;
            /*
            $fields = $this->getFields();
            $i = $this->in_array_ic ($field, $fields);
            if( $i == false ) 
                return false;
            
            $field = $fields[$i];
            */
            $service = $this->getServiceByName($name);
            if ($service == false)
                return false;
            return $service[$field];


        }

        public function getTagValueByName( $tagKey, $name) {
            return false;
            
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
            return false;
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
            $client = $this->getClientIam();
            
            try{
                $result = $client->listPolicies([]);
            }catch( AwsException $e){
                return false;
            }

            if( !is_object($result) || !is_array( $result['Policies'] )  )
                return false;

            $services = $result['Policies'];

            foreach ( $services as $service ){
                if( strcmp ($service['PolicyId'], $id ) == 0 ) 
                    return $service;
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
            
            if($name != true)
                return false;

            $client = $this->getClientIam();

            try{
                $result = $client->listPolicies([]);
            }catch( AwsException $e){
                return false;
            }

            if( !is_object($result) || !is_array( $result['Policies'] )  )
                return false;

            $services = $result['Policies'];

            foreach ( $services as $service ){
                if( strcmp ($service['PolicyName'], $name ) == 0 ) 
                    return $service;
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
            return false;

            if($name != true)
                return false;

            $client = $this->getClientEfs();
            
            try{
                $result = $client->describeFileSystems([
                    #'Filters' => [
                    #    [
                    #        'Name' => 'tag:Name',
                    #        'Values' => [$name],
                    #    ]
                    #] 
                ]);
        
            }catch( AwsException $e){
                
                return false;
            }

            if( !is_object($result ) || !is_array( $result ['FileSystems'] )    )
                return false;




            $services = $result['FileSystems'];
            
            foreach( $services as $service ){
                $tags = $service['Tags'];
                foreach( $tags as $tag ){
                    if( $tag['Key'] == 'Name' && $tag['Value'] == $name)
                        return $service;
                }


            }
            
            
            return false;

        }

        public function delete( $name ){
            $service = $this->getServiceByName( $name );
            if( $service == false )
                return true;

            #if( !$this->exists($name) )
                #echo PHP_EOL . "bucket no existe " . PHP_EOL;
            #    return true;
                
            try{
                $client = $this->getClientIam();
                $result = $client->deletePolicy([
                    'PolicyArn' => $service['Arn'], // REQUIRED
                ]);
                echo "\nL370 - OK - deletePolicy \n";
                return $this->waitToBeDeleted( $name );

                 
            }catch(AwsException $e){
                $e->getMessage();
                echo "\nL376 - KO - deletePolicy \n";

                return false;

            }

        }

        public function exists( $name ){
            return $this->getServiceByName(  $name );
        }

        public function existsById( $id ){
            return false;

            if( $id != true)
                return false;

            $service = $this->getServiceById( $id );
            if( $service == false)
                return false;

            return true;

        }


        public function existsByName( $name ){
            
            #return $this->getServiceByName(  $name );

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
            return false;

            $service = $this->getServiceById( $id );
            if( isset( $service['LifeCycleState'] ) && strcmp( $service['LifeCycleState'], 'available' ) == 0 )
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
            return false;

            return $this->getServiceByName(  $name );
    
            $service = $this->getServiceByName( $name );
            return $this->isAvailableById( $service['FileSystemId'] );



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

        public function waitToBeCreated( $name, $seconds = null, $times = null ){
            
            if($seconds != true)
                $seconds = 10;
        
            if($times != true)
                $times = 6;
            
            #$clientEc2 = $this->getClientEc2();

            for($i = 0; $i < $times;  $i++ ){
                $service = $this->getServiceByName( $name );
                #$service = $this->getServiceById( $id );
                #var_export( $ig );
                if( is_array( $service )  )
                    return true;                
                sleep($seconds);
            }

            return false;
        }

        public function waitToBeDeleted( $name, $seconds = null, $times = null ){

            if($seconds != true)
                $seconds = 10;
    
            if($times != true)
                $times = 6;
            
            for($i = 0; $i < $times;  $i++ ){
                $service = $this->getServiceByName( $name );
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
            return false;
            return $this->id;
        }


        public function getIdByName( $name ) {
            $client = $this->getClientIam();
            $result = $client->listPolicies([]);

            if( !isset( $result['Policies'] ) || !is_array( $result['Policies'] ) )
                return false;

            foreach( $result['Policies'] as $service ){
                if( strcmp( $service['PolicyName'], $name ) == 0   )
                    return $service['PolicyId'];
            }

            return false;
        }



        public function setId( $id ) {
            $this->id = $id;
        }

        public function getFields(){
            return false;

            return $this->fields;
        }

        public function getStatus ($id ){
            return false;

            $service = $this->getServiceById( $id );
            
            if ( isset( $service['LifeCycleState'] )  )
                return $service['LifeCycleState'];
            return false;
        
        }


        public function getStatusById ($id ){
            return false;

            return $this->getStatus( $id );
        }


        public function getStatusByName ($name ){
            return false;
            
            $service = $this->getServiceByName( $name );
            
            return $this->getStatus(  $service[ 'FileSystemId' ]  );



            #if ( $service == false)
            #    return false;

            #return $service['State'];

        }




    }

?>
