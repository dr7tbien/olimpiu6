<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    require_once 'classes/CDH_abstractAwsService.php';

    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    class CDH_awsInstanceProfile extends CDH_abstractAwsService {
    
        


        public function __construct(){
            $this->setCredentials();
            $this->setClientIam();
            $this->setClassName("CDH_awsInstanceProfile");
        }
        
        public function create ( $data = [] ) {

            $client = $this->getClientEc2();
            #$id     = $data['DBInstanceIdentifier'];
            $name   = $data['InstanceProfileName'];
            
            try{
                if ( isset($id) && $this->existsById( $id ) )
                    throw new Exception( "L044 El servicio " . $this->getClassName() . " con la id " . $id . " ya existe");
                
                if ( isset($name) && $this->existsByName( $name ) )
                    throw new Exception( "L047 El servicio " . $this->getClassName() . " con el nombre " . $name . " ya existe");
                
                $result     = $client->createInstanceProfile( $data );
                #$service    = $result['HostedZone'];
                $identifier = $result['InstanceProfile']['InstanceProfileId'];

                if( $this->waitToBeCreated(  $identifier  ) == false )
                    throw new Exception( "L054 Demasiado tiempo esperando a la creación del servicio " . $this->getClassName() );

                return $result['Instances'] ;
            
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
            $client = $this->getClientIam();            

            if( !isset ( $data['InstanceProfileName'] )  )
                return false;

            if ( $this->existsByName( $data['InstanceProfileName'] ) ){    
                $msg = "KO;INSTANCEPROFILE;002;144";
                return true;
            }

            try{
                $result = $client->createInstanceProfile( $data );
                if( $this->waitToBeCreated(  $result['InstanceProfile']['InstanceProfileId']  ) == false ) 
                    throw new Exception("\nL136 Error createInstanceProfile -  waitToBeCreated\n");


            }catch (AwsException $e) {
                echo $e->getMessage();
                die("\nL141 - Error creando Instance Profile\n");
            }catch(Exception $e){
                echo $e->getMessage();
                die("\nL144 - Error creando Instance Profile\n");
            }

            return $result['InstanceProfile'];

        }


        public function getArnByName($data) {
            $client = $this->getClientIam();            

            $result = $client->listInstanceProfiles([]);
            $services = $result['InstanceProfiles'];

            foreach( $services as $service ){
                if( $service['InstanceProfileName'] == $data )
                    return $service['Arn'];
            }
            return false;
        }


        /**
         * Esta función carece de sentido, pues los únicos valores interesantes son el Nombre y la Id y ambos los tenemos
         *  
         * */
        public function getFieldValueById ( $field, $id ) {
            return false;

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
            return false;

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
            $result = $client->listInstanceProfiles([]);
            #echo "\nNNNNNN------ Service .......";
            #var_export( $result );
            #echo "\n//////////// Service .......";

            try{
                $result = $client->listInstanceProfiles([]);
            }catch( AwsException $e){
                return false;
            }



            if( !is_object($result ) || !is_array( $result ['InstanceProfiles'] )    )
                return false;

            $services = $result['InstanceProfiles'];

            foreach( $services as $service ) {

                if (  strcmp( $service['InstanceProfileId'], $id ) == 0 )
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
                $result = $client->listInstanceProfiles([]);
            }catch( AwsException $e){
                return false;
            }
            if( !is_object($result ) || !is_array( $result ['InstanceProfiles'] )    )
                return false;

            $services = $result['InstanceProfiles'];

            foreach( $services as $service ) 
                if (  strcmp( $service['InstanceProfileName'], $name ) == 0 )
                    return $service;

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


        public function removeRoleFromInstanceProfile( $data = [] ){
            
            $client = $this->getClientIam();
            
            try{
            
                $client->removeRoleFromInstanceProfile($data);
                return true;
            }catch( AwsException $e){
                echo $e->getMessage();
                return false;
            }


        }

        public function deleteInstanceProfile( $name ){
            return $this->delete( $name );
        }

        public function delete( $name ){

            if( !$this->exists($name) )
                return true;

            $client =  $this->getClientIam();

            try{
                $result = $client->deleteInstanceProfile([
                    'InstanceProfileName' => $name , // REQUIRED
                ]);
               
            }catch(AwsException $e) {
                echo $e->getMessage();
                return false;
            }

            return true;


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
                #echo "No es true";    
                return false;
            #echo "L464\n";
            $service = $this->getServiceByName( $name );

            #var_export( $service ); 
            #die( "L449" );

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

        public function isAvailableById( $data ){


            return $this->existsById( $data );

            $service = $this->getServiceById( $data );
            if( isset( $service ) &&  is_array( $service ) )
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

        public function isAvailableByName( $data ){
            return $this->existsByName( $data );
        }


        /**
         * @param string    $data       id del saervicio
         * @param int       $seconds    Tiempo que duerme el itinerador
         * @param int       $times      Número máximo de comprobaciones
         * 
         */

        public function waitToBeCreated( $data, $seconds = null, $times = null ){
            
            if($seconds != true)
                $seconds = 10;
        
            if($times != true)
                $times = 6;
            
            #$clientEc2 = $this->getClientEc2();
            
            for($i = 0; $i < $times;  $i++ ){
                $service = $this->getServiceById( $data );
                
                


                if( is_array( $service )  )
                    return true;                
                sleep($seconds);
            }

            return false;
        }

        public function waitToBeDeleted( $data, $seconds = null, $times = null ){

            if($seconds != true)
                $seconds = 10;
    
            if($times != true)
                $times = 6;
            
            $client = $this->getClientIam();

            
            for($exists = false, $i = 0; $i < $times;  $i++ ){
                $result = $client->listInstanceProfiles([]);
                $ips    = $result['InstanceProfiles'];
                foreach( $ips as $ip) {
                    if( $ip['InstanceProfileId'] == $name )
                        $exists = true; 
                }
                if( $exists == false )
                    return true;
 
                sleep($seconds);
                $exists = false;
            }

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

        public function getIdByName( $data ) {

            $service = $this->getServiceByName( $data );
            if( isset( $service[ 'InstanceProfileId' ] ) )
                return $service[ 'InstanceProfileId' ];
            
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

        }




    }

?>