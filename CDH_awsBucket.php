<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    require_once 'classes/CDH_abstractAwsService.php';
    #require_once 'defines/services_aws/CDH_awsDefineFileSystem.php';

    require_once 'classes/CDH_awsVpc.php';        
    require_once 'classes/CDH_awsSecurityGroup.php';        
    require_once 'classes/CDH_awsSubnet.php';        
    require_once 'classes/CDH_awsMountTarget.php';   
    require_once 'classes/CDH_awsAccessPoint.php';   
         


    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    class CDH_awsBucket extends CDH_abstractAwsService {
        
        
  
        
       
        public function __construct(){
            $this->setCredentials();
            $this->setClientS3();
            $this->setClassName("CDH_awsBucket");

        }
        
        public function create ( $data = [] ) {

            $client = $this->getClientS3();
            #$id     = $data['DBInstanceIdentifier'];
            $name   = $data['Bucket'];
            
            try{
                if ( isset($id) && $this->existsById( $id ) )
                    throw new Exception( "L044 El servicio " . $this->getClassName() . " con la id " . $id . " ya existe");
                
                if ( isset($name) && $this->existsByName( $name ) )
                    throw new Exception( "L047 El servicio " . $this->getClassName() . " con el nombre " . $name . " ya existe");
                
                $result     = $client->createBucket( $data );
                #$service    = $result['HostedZone'];
                $identifier = $data['Bucket'];

                if( $this->waitToBeCreated(  $identifier  ) == false )
                    throw new Exception( "L054 Demasiado tiempo esperando a la creación del servicio " . $this->getClassName() );

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

        public function putDataInObject($bucket, $path, $body){
            $client = $this->getClientS3();

            try{
                $result = $client->putObject([
                    'Bucket' => $bucket,
                    'Key'    => $path,
                    #'Body'   => 'tron tron',
                    'Body'   => $body,
                    #'Body'  => <BLOB>,
                    #'SourceFile' => 'test.txt'
                ]);    

            }catch( AwsException $e ){
                return false;
            }

            return true;

        }
        
        public function getDataFromObject($bucket, $path ){
            $client = $this->getClientS3();

            try{
                $result = $client->getObject([
                    'Bucket' => $bucket,
                    'Key' => $path,
                ]);
                

            }catch( AwsException $e ){
                return false;
            }

            return trim( $result['Body'] );

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

        /**
         * VERIFICADO
         * 
         * 
         */


        public function getTagValueByName( $name, $key) { 
             
            if( $key != true || $name != true )
                return false;
        
            #echo "\nL292 \$name = $name | \$key = $key";

            $client = $this->getClientS3();

            try{
                $result = $client->getBucketTagging([
                    'Bucket' => $name, // REQUIRED
                ]);    

                $tags = $result['TagSet'];
            }catch( AwsException $e ){
                echo "\nL276 " . $e->getMessage();
                return false;
            }
            
            

            #var_export( $tags );

            foreach( $tags as $tag ){
                #var_export( $tag );
                #echo "\n\$key = $key | \$tag['Key'] = " . $tag['Key'] . "\$tag['Value'] = " . $tag['Value'];
                if( preg_match( "/^$key$/", $tag['Key'] ) )
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
            return false;

            if($id != true)
                return false;
            $client = $this->getClientEfs();
            try{
                $result = $client->describeFileSystems([
                    'FileSystemId' => $id,
                ]);
            }catch( AwsException $e){
                return false;
            }
            if( !is_object($result ) || !is_array( $result ['FileSystems'] ) || count( $result['FileSystems'] ) != 1   )
                return false;

            $services = $result['FileSystems'];

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

            
            #return $this->getServiceByTagName( $name );
            
            
            if($name != true)
                return false;

            $client = $this->getClientS3();

            try{
                $result = $client->listBuckets([]);
            }catch( AwsException $e){
                return false;
            }

            if( !is_object($result) || !is_array( $result['Buckets'] )  )
                return false;

            $services = $result['Buckets'];

            
            foreach ( $services as $service ){
                #echo PHP_EOL . "\$service = " . $service['Name'] . " | \$name = $name";    
                if( strcmp ($service['Name'], $name ) == 0 ) 
                    return $service;
                
            }    

            #echo PHP_EOL;
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
            if( !$this->exists($name) )
                #echo PHP_EOL . "bucket no existe " . PHP_EOL;
                return true;
                
            try{
                $client = $this->getClientS3();
                $result = $client->deleteBucket([
                    'Bucket' => $name, // REQUIRED
                ]);
                
            }catch(AwsException $e){
                $e->getMessage();
                return false;

            }

            return $this->waitToBeDeleted( $name  );


        }


        public function existsObject( $bucket, $obj ){
            $data = [
                    'Bucket' => $bucket, // REQUIRED
                    'Key' => $obj, // REQUIRED
            ];

            return $this->getObject(  $data );


        } 

        public function deleteObject( $bucket, $path ){ 

            $client = $this->getClientS3();
            
            if ( !$this->existsObject( $bucket, $path )   )
                return false;


            try{

                $result = $client->deleteObject([
                    'Bucket' => $bucket,
                    'Key' => $path, // REQUIRED
                ]);

                return true;

            }catch(AwsException $e){
                #echo $e->getMessage();
                return false;

            }


        }




        public function getObject( $obj = [] ){
            $client = $this->getClientS3();
            
            try{

                return $client->getObject( $obj );            
            
            
            }catch ( AwsException $e ) {
                $e->getMessage();
                return false;

            }
        }



        public function getObjects( $datos ){
            
            $client = $this->getClientS3();
            try{
                $result = $client->listObjects( $datos );
                return $result[ 'Contents' ];
            }catch ( AwsException $e ) {
                $e->getMessage();
                return false;
            }
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
            
            return $this->getServiceByName(  $name );

            if( $name != true)
                #echo "No es true";    
                return false;
            #echo "L464\n";
            $service = $this->getServiceByName( $name );

            #var_export( $service ); 
            #die( "L468" );

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
            return false;

            $service = $this->getServiceByName( $name );
            if( isset( $service[ 'FileSystemId' ] ) )
                return $service[ 'FileSystemId' ];
            
            return false;
        }

        public function setId( $id ) {
            $this->id = $id;
        }

        public function getFields(){
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


        public function deleteBucketStartsWith( $starts ) {

            if( preg_match ("/^cdh-image-/i", $starts ) != 1  ) {
                echo "Los buckets a borrar han de copmenzar por \"cdh-image_\" ";
                return false;
            }

            if ( strlen( $starts ) < 6 ) {
                $msg = "Argumento \$starts demasiado corto";
                return false;
            }
            
            $client = $this->getClientS3();
            
            $result = $client->listBuckets([]);
                        
            $buckets = $result['Buckets'];

            $bucketNamesDeleted = [];

            foreach ( $buckets as $bucket ) {
                if( preg_match ("/^$starts/i", $bucket['Name'] ) == 1 ) {
                    
                    $this->delete(  $bucket['Name']  );
                    $bucketNamesDeleted[] = $bucket['Name'];
                }
            }
            return $bucketNamesDeleted;
        }


        public function getLastBucketBeginsWith( $starts ){
            $client = $this->getClientS3();
            
            $result = $client->listBuckets([]);
            $buckets = $result['Buckets'];
            
            $time = 15;
            $times = 30;
            $bucketName = "";

            for($existe = false, $i = 0; $i < $times; $i++, sleep($time ) ) {
                $result = $client->listBuckets([]);
                $buckets = $result['Buckets'];
                foreach($buckets as $bucket){
                    if( strpos(  $bucket ['Name'], $starts ) === 0  ){
                        #$bucketName = $bucket ['Name'];
                        $existe = true;
                        break;
                    } 
                

                }
            
                if( $existe == true )
                    break;
            
            }

            if( $existe == false ){
                echo "\nL954 No existe bucket que comience por $starts ";
                return false;

            }
                
            $result = $client->listBuckets([]);
            $buckets = $result['Buckets'];



            for( $buks = [], $j = $i = 0; $i < count( $buckets ); $i++  ){
                #echo "\nL932  \$buckets[ $i ]['Name'] = " . $buckets[ $i ]['Name'] . " | \$starts = $starts";
                #echo "\nL933   strpos(" . $buckets[ $i ]['Name'] . "," . " $starts ) = " . strpos(  $buckets[ $i ]['Name'], $starts );     
                if( strpos(  $buckets[ $i ]['Name'], $starts ) === 0  ) 
                    $buks[ strval( $buckets[$i]['CreationDate'] ) ] = $buckets[$i]['Name'];
    
            }

            krsort( $buks );

            $keys = array_keys( $buks );

            if(  is_array( $buks ) && count( $buks ) > 0 && is_array( $keys ) && count( $keys ) > 0 )
                return $buks[ $keys[0] ];
            return false;

        }


    }

?>
