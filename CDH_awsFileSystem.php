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
    

    class CDH_awsFileSystem extends CDH_abstractAwsService {
        
        
        
        private $fields = [
            'CreationTime', # => <DateTime>,
            'CreationToken', # => '<string>',
            'Encrypted', # => true || false,
            'FileSystemArn', #  => '<string>',
            'FileSystemId', #  => '<string>',
            'KmsKeyId', #  => '<string>',
            'LifeCycleState', #  => 'creating|available|updating|deleting|deleted',
            'Name', #  => '<string>',
            'NumberOfMountTargets', #  => <integer>,
            'OwnerId', #  => '<string>',
            'PerformanceMode', #  => 'generalPurpose|maxIO',
            'ProvisionedThroughputInMibps', #  => <float>,
            'SizeInBytes', #  => [ 
                #'Timestamp' => <DateTime>,
                #'Value' => <integer>,
                #'ValueInIA' => <integer>,
                #'ValueInStandard' => <integer>,
            #],
            'Tags', #  => [
            #    [
            #        'Key' => '<string>',
            #        'Value' => '<string>',
            #    ],
            #    // ...
            #],
            #'ThroughputMode' => 'bursting|provisioned',
        ];
        


        public function __construct(){

            $this->setCredentials();
            $this->setClientEfs();
        }
        
        /**
         * Esta función crea un objeto suibnet de AWS.
         * Si existe carga los datos del existente en el objeto
         * 
         * @param string $pos ['0', '1', '2']
         * 
         * @param string $type ['public', 'private', 'database']
         * 
         * @return array serviceAWS un array copn los contenidos de la subnet
         * 
         */

        /*
        public function createForCloudator() {
            $vpc    = new CDH_awsVpc(); 
            $sg     = new CDH_awsSecurityGroup();
            $sn     = new CDH_awsSubnet();
            $mt     = new CDH_awsMountTarget();
            $ap     = new CDH_awsAccessPoint();

            $vpcId  = $vpc->getIdByName( CDH_VPC_NAME );
            $snId_0 = $sn->getIdByName ( CDH_SUBNET_PRIVATE_0_Name );
            $snId_1 = $sn->getIdByName ( CDH_SUBNET_PRIVATE_1_Name );
            $snId_2 = $sn->getIdByName ( CDH_SUBNET_PRIVATE_2_Name );

            #echo PHP_EOL . "\$vpcId = "     . $vpcId ;
            #echo PHP_EOL . "\$snId_0 = "    . $snId_0;
            #echo PHP_EOL . "\$snId_1 = "    . $snId_1 ;
            #echo PHP_EOL . "\$snId_2 = "    . $snId_2;
            #echo PHP_EOL;

                        
            #$efsId  = $this->create ( CDH_FILESYSTEM );
            $efs  = $this->create ( CDH_FILESYSTEM );
            
            $efsId = $efs['FileSystemId'];
            
            
            #echo PHP_EOL . "\$efsId = "    . $efsId;
            
            $cdh_filesystem_sg          = CDH_FILESYSTEM_SG;
            $cdh_filesystem_sg['VpcId'] = $vpcId;
        
            $sgId   = $sg->create   ( $cdh_filesystem_sg );


            $cdh_filesystem_sg_ingress = CDH_FILESYSTEM_SG_INGRESS;
            $cdh_filesystem_sg_ingress['GroupId'] = $sgId;

            $sgRules  = $sg->createRulesIngress( $cdh_filesystem_sg_ingress );
            #$sgId   = "sg-0bb2dae1f37312f70";

            #echo PHP_EOL . "\$sgId = "    . $sgId;
           
            $mtData_0                       = $mtData_1                     = $mtData_2                     = CDH_EFS_MOUNT_TARGET;
            $mtData_0['FileSystemId']       = $mtData_1['FileSystemId']     = $mtData_2['FileSystemId']     = $efsId;
            $mtData_0['SecurityGroups'][]   = $mtData_1['SecurityGroups'][] = $mtData_2['SecurityGroups'][] = $sgId;
            
            $mtData_0['SubnetId'] = $snId_0;
            $mtData_1['SubnetId'] = $snId_1; 
            $mtData_2['SubnetId'] = $snId_2; 

            $mt_0    = $mt->create( $mtData_0 );
            $mt_1    = $mt->create( $mtData_1 );
            $mt_2    = $mt->create( $mtData_2 );

            $mountTargetId_0    = $mt_0['MountTargetId'];
            $mountTargetId_1    = $mt_1['MountTargetId'];
            $mountTargetId_2    = $mt_2['MountTargetId'];
                        

            $cdh_efs_access_point = CDH_EFS_ACCESS_POINT;
            $cdh_efs_access_point['FileSystemId'] = $efsId;

            $resAccess          = $ap->create($cdh_efs_access_point );
            
            return true;
        }
        */
        

        

        public function create($data = []) {

            if( $data != true || !is_array($data) )
                return false;

            $client = $this->getClientEfs();

            try{
                /*               
                if ( $this->existsByName( $name ) ) {        
                    $msg = "OK;EFS;002;79";
                    throw new Exception( $msg );
                }
                */

                $result = $client->createFileSystem( $data );

                if( $this->waitToBeCreated(  $result['FileSystemId']  ) == false ){
                    $msg = "OK;FILESYSTEM;002;91";
                    throw new Exception( $msg );
                }
                                    
            }catch (AwsException $e) {
                echo $e->getMessage();
                return false;
            }catch(Exception $e){
                echo $e->getMessage();
                return false;
            }
            
            
            return $result;

            #return $result['FileSystemId'];


        }


        public function getFileSystemEndPoint( $fsId, $region  ){
            return $fsId . ".efs." . $region . ".amazonaws.com";
        }

        public function getFileSystemEndPointByName( $name, $region ){
            $client = $this->getClientEfs();
            
            /*            
            try{
                $result = $client->describeFileSystems([]);
            }catch( AwsException $e){
                return false;
            } */
            $result = $client->describeFileSystems([]);
            if( !isset($result['FileSystems'] )     )
                return false;

            $services = $result['FileSystems'];
            #echo "\$name = $name";
            foreach( $services as $service ){
                $tags = $service['Tags'];
                foreach( $tags as $tag ){
                    
                    if( $tag['Key'] == 'Name' && $tag['Value'] == $name){
                        $srv = $service;
                        break;
                    }
                        
                }
            }

            if( !isset( $srv ) )
                return false;
            #$service = $this->getServiceByName( $name );
            return $this->getFileSystemEndPoint( $srv['FileSystemId'], $region  );
        }

        public function getEfsEndPoint( $efsId, $region  ){

            return $this->getFileSystemEndPoint($efsId, $region);
        }

        public function getEfsEndPointByName( $name, $region ){
            
            #return $this->getFileSystemEndPointByName( $service['FileSystemId'], $region  );
            return $this->getFileSystemEndPointByName( $name, $region  );
        }
        /*
        public function createMountTarget( $fsId, $sgId, $snId ){
            if( $fsId != true || $sgId != true || $snId != true )
                return false;


            $clientEfs = $this->getClientEfs(); 

            try{
                $resultEfs = $clientEfs->createMountTarget ([
                    'FileSystemId' => $fsId, // REQUERIDO
                    //'IpAddress' => '<cadena>',
                    'SecurityGroups' => [$sgId],
                    'SubnetId' => $snId, // REQUERIDO
                ]);

            }catch( AwsException $e ) {
                return false;
            }

            return $resultEfs['MountTargetId'];

        }
        
        */

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
            
            return $this->getServiceByTagName( $name );
            
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

            $client = $this->getClientEfs();
            $result = $client->describeFileSystems([]);
            /*
            try{
                $result = $client->describeFileSystems([
                    #'Filters' => 
                    #    [
                    #        'Name' => 'tag:Name',
                    #        'Values' => [$name],
                    #    ]
                    #] 
                ]);
        
            }catch( AwsException $e){
                
                return false;
            }
            */
            
            if( !isset ( $result ['FileSystems'] ) || !is_array( $result ['FileSystems'] )    )
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


        public function delete( $id ){
            if( !$this->exists($id) )
                return false;
            
            try{
                $client = $this->getClientEfs();
                $result = $client->deleteFileSystem([
                    'FileSystemId' => $id, // REQUIRED
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
            #echo "\$name = $name \n";
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

        public function waitToBeCreated( $id, $seconds = null, $times = null ){
            if($seconds != true)
                $seconds = 10;
        
            if($times != true)
                $times = 6;
            
            #$clientEc2 = $this->getClientEc2();

            for($i = 0; $i < $times;  $i++ ){
                $status = $this->getStatusById( $id );
                #$service = $this->getServiceById( $id );
                #var_export( $ig );
                if( $status != false && strcmp( $status, 'available' ) == 0 )
                    return true;                
                sleep($seconds);
            }

            return false;
        }

        public function waitToBeDeleted( $id, $seconds = null, $times = null ){
            if($seconds != true)
                $seconds = 10;
    
            if($times != true)
                $times = 6;
            
            $clientEc2 = $this->getClientEc2();

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

            $service = $this->getServiceByName( $name );
            if( isset( $service[ 'FileSystemId' ] ) )
                return $service[ 'FileSystemId' ];
            
            return false;
        }

        public function getIdByTagName( $name ) {

            $service = $this->getServiceByTagName( $name );
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
            #return true;

            $service = $this->getServiceById( $id );
            
            if ( isset( $service['LifeCycleState'] )  )
                return $service['LifeCycleState'];
            return false;
        
        }


        public function getStatusById ($id ){
            #return true;
            return $this->getStatus( $id );
        }


        public function getStatusByName ($name ){
            #return true;
            $service = $this->getServiceByName( $name );
            
            return $this->getStatus(  $service[ 'FileSystemId' ]  );



            #if ( $service == false)
            #    return false;

            #return $service['State'];

        }




    }

?>
