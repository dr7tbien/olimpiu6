<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    require_once 'classes/CDH_abstractAwsCloudator.php';


    require_once 'classes/CDH_awsEfs.php';
    require_once 'classes/CDH_awsSecurityGroup.php';
    require_once 'classes/CDH_awsKeyPair.php';
    require_once 'classes/CDH_awsPolicy.php';
    require_once 'classes/CDH_awsRole.php';


    require_once 'classes/CDH_awsSubnet.php'                ;
    require_once 'classes/CDH_awsTargetGroup.php'           ;
    require_once 'classes/CDH_awsListener.php'           ;
    
    require_once 'classes/CDH_awsLoadBalancer.php'          ;
    require_once 'classes/CDH_awsLaunchConfiguration.php'   ;
    require_once 'classes/CDH_awsAutoScalingGroup.php'      ;
    require_once 'classes/CDH_awsInstanceProfile.php'      ;
    require_once 'classes/CDH_awsInstance.php'      ;
    require_once 'classes/CDH_awsImage.php'      ;

    require_once 'classes/CDH_awsBucket.php';
    

    require_once 'classes/CDH_awsListener.php';       
    require_once 'defines/CDH_awsDefinesAll.php';
    require_once 'defines/services_aws/CDH_awsDefineStep0NetSystem.php';
    require_once 'defines/services_aws/CDH_awsDefineStep1FileSystem.php';
    require_once 'defines/services_aws/CDH_awsDefineStep3ImageSystem.php';
    #require_once 'defines/services_aws/CDH_awsDefineStep4LoadBalancingSystem.php';
    #require_once 'defines/services_aws/CDH_awsDefineStep5AutoScalingGroupFrontEndSystem.php';
    #require_once 'defines/services_aws/CDH_awsDefineStep6AutoScalingGroupBackEndSystem.php';
    #require_once 'defines/services_aws/CDH_awsDefineStep7MoveDbLocalToCloudSystem.php';
    require_once 'defines/services_aws/CDH_awsDefineStep8MoveFilesLocalToCloudSystem.php';

    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    class CDH_awsStep8MoveFilesLocalToCloudSystem extends CDH_abstractAwsCloudator{
        
        #private $clientCwt;
        #private $subnet0Name;
        #private $subnet1Name;
        #private $subnet2Name;
        

        public function __construct(){
            /*
            if ( touch( CDH_IMAGE_FILE_USER_DATA ) == false){
                echo "\nL035 No touched file. " . CDH_IMAGE_FILE_USER_DATA . "\n";
                die('\nL036 Problemas touching\n');
            }
            */

            #$this->createNames();

        }

    
        public function createForCloudator() {

            #region Conect
                
           /* $service = $this->serviceConect();
            if( $service == false ) 
                die("\nL080 - KO Error en serviceFilesLocalToCloudConect\n");
            echo "\nL081 - OK serviceLocal2CloudConect finalizado\n";*/
            
            #endregion Conect
            
            #region Transmission
            $service = $this->serviceTransmission();
            if( $service == false ) 
                die("\nL087 - KO serviceFilesLocalToCloudTransmission no creado\n");
            echo "\nL088 - OK serviceLocal2CloudTransmission finalizado\n";
            #endregion Transmission
            die("\n85 *********");
            #region Ending            
            $service = $this->serviceEnding  ();
            if( $service == false ) {
                echo "\nL095 - KO serviceFilesLocalToCloudEnding no creado\n" ;
                return true;
            }
            echo "\nL096 - OK serviceLocal2CloudEnding finalizado\n";
            #endregion Ending

            echo "\nL156 - OK TODO serviceFilesLocalToCloud creado\n";

            return true;
                        
        }
        
        private function serviceConect(){
            #$this->instanceTerminate    ();
            
            $this->policyCreate         ();
            $this->roleCreate           ();            
            $this->securityGroupCreate  ();
            $this->userDataFileModify   (); 
            $this->keyPairCreate        ();
            $this->copyFilesLocalInFile ();
            
            return true;
        }
        
        private function serviceTransmission(){
            #$this->stopCms();
            $this->instanceRun();
            return true;
        }

        private function serviceEnding(){
            $this->instanceTerminate();
            $this->keyPairDelete();
            $this->securityGroupDelete();
            $this->roleDelete();
            $this->policyDelete();
            $this->bucketDelete();
            return true;
        }


        #region serviceConnect

        public function policyCreate(){
            $service        = new CDH_awsPolicy();
            $data           = CDH_FILES_LOCAL_TO_CLOUD_createPolicy;
            #return true;
            $service        = $service->create( $data );
            if( $service == false)
                die("\nL150 - Policy no creado\n");
            echo "\nL151 - Policy creado\n";
            return true;

        }

        public function roleCreate(){
            #1. Create Role
            #2. Create InstanceProfile
            #3. Add Role to Instance Profile
            #4. Attach Role Policy 

            $role               = new CDH_awsRole();
            $policy             = new CDH_awsPolicy();
            $instanceProfile    = new CDH_awsInstanceProfile();

            $dataAddRoleToInstanceProfile = [
                'InstanceProfileName'   => CDH_FILES_LOCAL_TO_CLOUD_createRole['RoleName'], // REQUIRED
                'RoleName'              => CDH_FILES_LOCAL_TO_CLOUD_createRole['RoleName'], // REQUIRED
            ];

            $dataAttachRolePolicy = [
                   'PolicyArn' => $policy->getArnByName( CDH_FILES_LOCAL_TO_CLOUD_createPolicy['PolicyName'] ), // REQUIRED
                   'RoleName' => CDH_FILES_LOCAL_TO_CLOUD_createRole['RoleName'], // REQUIRED
            ]; 

            #print_r($dataAddRoleToInstanceProfile);
            #print_r($dataAttachRolePolicy);
            #die("\nL172 \n");

            $resultRole             = $role->create                     ( CDH_FILES_LOCAL_TO_CLOUD_createRole );
            sleep(2);
            $resultInstanceProfile  = $instanceProfile->create          ( CDH_FILES_LOCAL_TO_CLOUD_createInstanceProfile );
            sleep(2);
            $resultAddRole          = $role->addRoleToInstanceProfile   ( $dataAddRoleToInstanceProfile );
            sleep(2);
            $resultAttachRolePolicy = $role->attachRolePolicy( $dataAttachRolePolicy );

            
            return true;


        }

        public function securityGroupCreate(){
            $vpc            = new CDH_awsVpc();
            $securityGroup  = new CDH_awsSecurityGroup();
            
            $data       = CDH_FILES_LOCAL_TO_CLOUD_createSecurityGroup;
            $dataRules  = CDH_FILES_LOCAL_TO_CLOUD_authorizeSecurityGroupIngress;

            $data['VpcId']  = $vpc->getIdByName( $this->getVpcName() );
            
            $result = $securityGroup->create( $data );

            $dataRules['GroupId'] = $result['GroupId'];

            $result = $securityGroup->authorizeSecurityGroupIngress( $dataRules );
        
            return true;  

        }

        public function userDataFileModify(){

            $efs        = new CDH_awsFileSystem();
            #$dbCluster  = new CDH_awsDBCluster();

            $efsId              = $efs->getIdByTagName( CDH_FILESYSTEM['Tags'][0]['Value'] );
            #die( "\n\$efsId = $efsId \n" );
            $endPointEfs        = $efs->getFileSystemEndPoint( $efsId, CDH_AWS_REGION  );
            #$endPointDbWriter   = $dbCluster->getEndPointWriter( CDH_DB_INSTANCE_createDBCluster['DBClusterIdentifier'] );

            $plantilla   = file_get_contents( CDH_FILES_LOCAL_TO_CLOUD_FILE_USER_DATA_TEMPLATE );

            if($plantilla){
                $search = array('{cdh_dominio_sin_punto}',
                               '{cdh_dominio_con_punto}',
                               '{cdh_aws_region}',
                               '{cdh_files_local_localdir_backup}',
                               '{cdh_files_local_localfile_backup}',
                               '{cdh_cms_name}',
                               '{cdh_cms_version}',
                               '{cdh_endpoint_efs}',
                               #'{CDH_BBDD_USER_CLOUD}',
                               #'{CDH_BBDD_PASSWD_CLOUD}',
                               #'{CDH_BBDD_NAME_CLOUD}',
                               #'{CDH_BBDD_ENDPOINT_CLOUD}',
                               '{cdh_dir_efs}',
                               '{cdh_files_local_to_cloud_bucket_name}',
                               '{cdh_files_local_to_cloud_bucket_tag_key}',
                               '{cdh_files_local_to_cloud_bucket_tag_value}'
                                );
                $replace = array(CDH_DOMINIO_SIN_PUNTO,
                                 CDH_DOMINIO_CON_PUNTO,
                                 CDH_AWS_REGION,
                                 CDH_FILES_LOCAL_LOCALDIR_BACKUP,
                                 CDH_FILES_LOCAL_LOCALFILE_BACKUP,
                                 CDH_CMS_NAME,
                                 CDH_CMS_VERSION,
                                 $endPointEfs,
                                 #CDH_CMS_DB_USER,
                                 #CDH_CMS_DB_PASSWD,
                                 #CDH_CMS_DB_NAME,
                                 #$endPointDbWriter,
                                 CDH_DIR_EFS,
                                 CDH_FILES_LOCAL_TO_CLOUD_BUCKET_NAME,
                                 CDH_FILES_LOCAL_TO_CLOUD_BUCKET_TAG_KEY,
                                 CDH_FILES_LOCAL_TO_CLOUD_BUCKET_TAG_VALUE
                                );
                $plantilla = str_replace($search, $replace, $plantilla);

                #intentar crear el fichero sh
                file_put_contents('store/scriptDbLocalToCloud.sh', $plantilla);
                return true;
                
            } else{ return false; }

            

        }

        public function keyPairCreate(){
            $service        = new CDH_awsKeyPair();
            $service        = $service->create( CDH_FILES_LOCAL_TO_CLOUD_keyPairCreate, CDH_FILES_LOCAL_TO_CLOUD_sshAccessFilePath );
            #return true;
            if( $service == false)
                die ("\nL252 - KEYPAIR no creado\n");
                
            return true;
        }

        #endregion serviceConnect



        #region serviceTransmission
        /**
         * Esta función comprueba si el CMS está activo. Si lo está desactiva el CMS
         * 
         */

        private function stopCms(){
            return true;
        }


        /**
         * Esta función copia la base de datos del CMS en un directorio del CMS
         * 
         * El directorio de guardado está definido en la constante
         * 
         * CDH_DATABASE_LOCALDIR_BACKUP. En este caso se ha definido como: store/CDH_dbLocal/
         * 
         * El comando de compresión es:
         * 
         * zip -r -s 10m CDH_filesLocal/prestashop.zip . -x cache -x CDH_filesLocal -x CDH_dbLocal
         * 
         * Este método hará lo siguiente:
         * 
         * 1. Copiará la base de datos en el directorio indicado por la constante CDH_DATABASE_LOCALDIR_BACKUP
         * 
         * 2. Comprime el archivo anterior en un zip igual o inferior a 100Mbs - En el caso de que el zip sea mayor 
         *      de 100Mbs el método comprimirá y dividirá el archivo .sql en partes de 100Mbs con el fiun de jutntarlos 
         *      posteriormente en AWS 
         * 3. Crea un archivo de texto en el directorio CDH_DATABASE_LOCALDIR_BACKUP definido en la constante 
         *      CDH_DATABASE_LOCALDIR_BACKUP_CONTENT_DESCRIPTION_FILE. Este archivo ha de ser un .txt con todas las urls
         *      de la fragmentación del zip
         * 
         * 
         *      https://nombre.de.url/uri.al.archivo/file0000000.zip
         *      https://nombre.de.url/uri.al.archivo/file0000001.zip
         *      https://nombre.de.url/uri.al.archivo/file0000002.zip
         *      https://nombre.de.url/uri.al.archivo/file0000003.zip
         *      https://nombre.de.url/uri.al.archivo/file0000004.zip
         *      https://nombre.de.url/uri.al.archivo/file0000005.zip
         *      https://nombre.de.url/uri.al.archivo/file0000006.zip
         *      https://nombre.de.url/uri.al.archivo/file0000007.zip
         * 
         * 
         * 5. Da permisos a todos los archivos del directorio para que puedan ser leidos desde fuera del servidor
         * 
         * 6, Cuando se finaliza la operación de duplicado dentro de aws de la base de datos se borra el directorio y sus archivos
         * 
         */

        private function copyFilesLocalInFile(){
            return true;

        }


        private function instanceRun(){


            $instance   = new CDH_awsInstance();
            $bucket     = new CDH_awsBucket();
            $ip         = new CDH_awsInstanceProfile();
            $sg         = new CDH_awsSecurityGroup();
            $subnet     = new CDH_awsSubnet();
            $image      = new CDH_awsImage();
        
            $ipService      = $ip->getServiceByName     (   CDH_FILES_LOCAL_TO_CLOUD_createInstanceProfile['InstanceProfileName'] );
            $ipArn          = $ipService['Arn'];

            $sgService      = $sg->getServiceByName     (   CDH_FILES_LOCAL_TO_CLOUD_createSecurityGroup  ['GroupName']  );
            $sgId           = $sgService['GroupId'];

            $subnetService  = $subnet->getServiceByName (   $this->getSubnetPublic0Name()       );
            $subnetId       = $subnetService['SubnetId'];


            $instance->deleteByTagName( CDH_FILES_LOCAL_TO_CLOUD_instanceRun['TagSpecifications'][0]['Tags'][0]['Value'] );


            $CDH_INSTANCE                                         = CDH_FILES_LOCAL_TO_CLOUD_instanceRun;
            $CDH_INSTANCE['IamInstanceProfile']['Arn']            = $ipService['Arn'];
            $CDH_INSTANCE['NetworkInterfaces'][0]['Groups'][0]    = $sgId;
            $CDH_INSTANCE['NetworkInterfaces'][0]['SubnetId']     = $subnetId;
            $CDH_INSTANCE['ImageId']                              = CDH_IMAGE_INSTANCE['ImageId'];
            #$CDH_IMAGE_INSTANCE['UserData']                             = base64_encode( file_get_contents( CDH_IMAGE_FILE_USER_DATA));
            
            #var_export( $CDH_INSTANCE );
            #die("\nL356\n");
            #return true;
            
            $instances  = $instance->create( $CDH_INSTANCE );
            echo "\nL316\n";
            if( $instances == false || !isset($instances[0]['InstanceId']) )
                return false;
                
            #$instanceId = $instances[0]['InstanceId'];
            
            $instanceName = $CDH_INSTANCE['TagSpecifications'][0]['Tags'][0]['Value'];

            $instanceId = $instance->getIdByName( $instanceName );


            $name   = CDH_FILES_LOCAL_TO_CLOUD_BUCKET_NAME;               
            $key    = CDH_FILES_LOCAL_TO_CLOUD_BUCKET_TAG_KEY;
            $value  = CDH_FILES_LOCAL_TO_CLOUD_BUCKET_TAG_VALUE; 
            echo "\nL325 - \$instanceId = $instanceId | \$name = $name \$key = $key \$value = $value\n";
            #die( "\n\$name = $name \$key = $key \$value = $value\n" );
            if($image->waitInstanceUserDataFinished( $name, $key, $value ) == false){
                echo "\nL328 BBB = false\n";
                return false;
            }

            echo "\nL328 BBB = true | \$instanceId = $instanceId \n";

            return $instanceId;


        }

        #endregion serviceTransmission






        #region serviceEnding



        public function bucketDelete(){
            $bucket = new CDH_awsBucket();
            $bucket->deleteBucketStartsWith( CDH_FILES_LOCAL_TO_CLOUD_BUCKET_ROOT_NAME );

        }

        public function instanceTerminate(){
            $instance = new CDH_awsInstance();
            return $instance->deleteByTagName( CDH_FILES_LOCAL_TO_CLOUD_instanceRun['TagSpecifications'][0]['Tags'][0]['Value'] );

        }

    

        public function keyPairDelete(){
            $keyPair = new CDH_awsKeyPair();
            if( !$keyPair->delete( CDH_FILES_LOCAL_TO_CLOUD_keyPairCreate['KeyName'] )  ){
                echo "\nL411 - No se ha podido borrar el KeyPair\n";
            }else{
                echo "\nL413 - Se ha podido borrar el KeyPair\n";
            }

            if( !$keyPair->delete( CDH_FILES_LOCAL_TO_CLOUD_sshAccessFilePath )  ){
                echo "\nL417 - No se ha podido borrar el KeyPair\n";
            }else{
                echo "\nL419 - Se ha podido borrar la key de acceso\n";
            }

            return true;
        }

        public function securityGroupDelete(){
            $sg     =  new CDH_awsSecurityGroup();
            $sgId   =  $sg->getIdByName( CDH_FILES_LOCAL_TO_CLOUD_createSecurityGroup['GroupName'] );
            #die( "\n\$sgId = $sgId\n" );
            if( !$sg->delete( $sgId )  ){
                echo "\nL430 - KO - No se ha podido borrar el SecurityGroup\n";
            }else{
                echo "\nL432 - OK - Borrado SecurityGroup\n";
            }
            return true;
        }

        public function roleDelete(){

            $role               = new CDH_awsRole();
            $policy             = new CDH_awsPolicy();
            $instanceProfile    = new CDH_awsInstanceProfile();


            $policyArn = $policy->getArnByName(CDH_FILES_LOCAL_TO_CLOUD_createPolicy['PolicyName']);

        
            $detachRolePolicy = [
                'PolicyArn' => $policyArn, // REQUIRED
                'RoleName' => CDH_FILES_LOCAL_TO_CLOUD_createRole['RoleName'], // REQUIRED
            ];


            $removeRoleFromInstanceProfile = [
                'InstanceProfileName'   => CDH_FILES_LOCAL_TO_CLOUD_createRole['RoleName'], // REQUIRED
                'RoleName'              => CDH_FILES_LOCAL_TO_CLOUD_createRole['RoleName'], // REQUIRED
            ];


            $role->detachRolePolicy             ( $detachRolePolicy                 );
            $role->removeRoleFromInstanceProfile( $removeRoleFromInstanceProfile    );
            $role->delete( CDH_FILES_LOCAL_TO_CLOUD_createRole['RoleName']             );
            #$policy->delete( CDH_FILES_LOCAL_TO_CLOUD_createPolicy['PolicyName']       );
            return true;
            
        }

        public function policyDelete(){
            $policy             = new CDH_awsPolicy();
            $policy->delete( CDH_FILES_LOCAL_TO_CLOUD_createPolicy['PolicyName']       );
            return true;
        }


        #endregion serviceEnding

    }





?>
