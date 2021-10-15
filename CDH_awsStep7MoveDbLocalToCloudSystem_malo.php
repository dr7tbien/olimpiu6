<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    require_once 'classes/CDH_abstractAwsCloudator.php';

    require_once 'classes/CDH_awsVpc.php';

    require_once 'classes/CDH_awsPolicy.php';
    require_once 'classes/CDH_awsRole.php';
    require_once 'classes/CDH_awsInstanceProfile.php';
    require_once 'classes/CDH_awsSecurityGroup.php';
    require_once 'classes/CDH_awsKeyPair.php';
    
    
    require_once 'classes/CDH_awsFileSystem.php';
    require_once 'classes/CDH_awsDBCluster.php';

    require_once 'classes/CDH_awsInstance.php'      ;
    require_once 'classes/CDH_awsBucket.php'      ;
    require_once 'classes/CDH_awsSubnet.php'      ;
    require_once 'classes/CDH_awsImage.php'      ;

    require_once 'defines/services_aws/CDH_awsDefineStep0NetSystem.php';
    require_once 'defines/services_aws/CDH_awsDefineStep1FileSystem.php';
    require_once 'defines/services_aws/CDH_awsDefineStep3ImageSystem.php';
    require_once 'defines/services_aws/CDH_awsDefineStep7MoveDbLocalToCloudSystem.php';

    require_once 'classes/lib/sql_dump/Mysqldump.php';
    

    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    class CDH_awsStep7MoveDbLocalToCloudSystem extends CDH_abstractAwsCloudator{
        
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

            $this->createNames();

        }

    
        public function createForCloudator() {
                       
            $this->copyDatabaseLocalInSqlFile();
            #die();
            #region Conect
            $service = $this->serviceConect();
            if( $service == false ) 
                die("\nL080 - KO Error en serviceDbLocalToCloudConect\n");
            echo "\nL081 - OK serviceDbLocalToCloudConect finalizado\n";
           
            #endregion Conect
            
            #region Transmission
            $service = $this->serviceTransmission();
            if( $service == false ) 
                die("\nL087 - KO serviceDbLocalToCloudTransmission no creado\n");
            echo "\nL088 - OK serviceDbLocalToCloudTransmission finalizado\n";
            #endregion Transmission
        
            #region Ending            
            $service = $this->serviceEnding  ();
            if( $service == false ) {
                echo "\nL095 - KO serviceDbLocalToCloudEnding no creado\n" ;
                return true;
            }
            echo "\nL096 - OK serviceDbLocalToCloudEnding finalizado\n";
            #endregion Ending

            echo "\nL156 - OK TODO serviceDbLocalToCloud creado\n";

            return true;
                        
        }

        private function serviceConect(){
        
            $this->policyCreate         ();
            echo "\n L113 Policy creado";
            
            $this->roleCreate           ();
            
            $this->securityGroupCreate  ();
            
            $this->userDataFileModify   ();
           
            $this->keyPairCreate        ();

            return true;
        }

        private function serviceTransmission(){
            $this->stopCms();
            $this->copyDatabaseLocalInSqlFile();
            $this->instanceRun();
            return true;
        }

        private function serviceEnding(){
            $this->instanceTerminate();
            sleep(5);
            $this->keyPairDelete();
            sleep(5);
            $this->securityGroupDelete();
            sleep(5);
            $this->roleDelete();
            sleep(5);
            $this->policyDelete();
            return true;
        }


        #region serviceConnect

        public function policyCreate(){
            $service        = new CDH_awsPolicy();
            $data           = CDH_DB_LOCAL_TO_CLOUD_createPolicy;
            #return true;
            $service        = $service->create( $data );
            if( $service == false)
                die("\nL141 - Policy no creado\n");
            echo "\nL144 - Policy creado\n";
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
                'InstanceProfileName'   => CDH_DB_LOCAL_TO_CLOUD_createRole['RoleName'], // REQUIRED
                'RoleName'              => CDH_DB_LOCAL_TO_CLOUD_createRole['RoleName'], // REQUIRED
            ];
            sleep(5);
            echo "L174 -- roleCreate";
            $dataAttachRolePolicy = [
                   'PolicyArn' => $policy->getArnByName( CDH_DB_LOCAL_TO_CLOUD_createPolicy['PolicyName'] ), // REQUIRED
                   'RoleName' => CDH_DB_LOCAL_TO_CLOUD_createRole['RoleName'], // REQUIRED
            ]; 

            print_r($dataAddRoleToInstanceProfile);
            print_r($dataAttachRolePolicy);

            $resultRole             = $role->create                     ( CDH_DB_LOCAL_TO_CLOUD_createRole );
            sleep(2);
             echo "L186 -- roleCreate";
            $resultInstanceProfile  = $instanceProfile->create          ( CDH_DB_LOCAL_TO_CLOUD_createInstanceProfile );
            sleep(2);
             echo "L189 -- roleCreate";
            $resultAddRole          = $role->addRoleToInstanceProfile   ( $dataAddRoleToInstanceProfile );
            sleep(2);
             echo "L192 -- roleCreate";
            $resultAttachRolePolicy = $role->attachRolePolicy           ( $dataAttachRolePolicy );

            
            return true;


        }

        public function securityGroupCreate(){
            $vpc            = new CDH_awsVpc();
            $securityGroup  = new CDH_awsSecurityGroup();
            
            $data       = CDH_DB_LOCAL_TO_CLOUD_createSecurityGroup;
            $dataRules  = CDH_DB_LOCAL_TO_CLOUD_authorizeSecurityGroupIngress;

            $data['VpcId']  = $vpc->getIdByName( $this->getVpcName() );
            
            $result = $securityGroup->create( $data );

            $dataRules['GroupId'] = $result['GroupId'];

            $result = $securityGroup->authorizeSecurityGroupIngress( $dataRules );
        
            return true;  

        }

        public function userDataFileModify(){

            $efs        = new CDH_awsFileSystem();
            $dbCluster  = new CDH_awsDBCluster();

            //$userData   = new CDH_PS_userDataChecker();


            $efsId              = $efs->getIdByTagName( CDH_FILESYSTEM['Tags'][0]['Value'] );
            #die( "\n\$efsId = $efsId \n" );
            $endPointEfs        = $efs->getFileSystemEndPoint( $efsId, CDH_AWS_REGION  );
            $endPointDbWriter   = $dbCluster->getEndPointWriter( CDH_DB_INSTANCE_createDBCluster['DBClusterIdentifier'] );

            #$amiVars            = $this->serviceDB2CloudGetVars( );
  
            #$efsEndPoint        = $amiVars['efsEndPoint'];
            #$dbEndPointWriter   = $amiVars['dbEndPointWriter'];


            $datosF = array();
            $datosF['CDH_ENDPOINT_EFS'          ]   = $endPointEfs;
            $datosF['CDH_BBDD_USER_CLOUD'       ]   = CDH_CMS_DB_USER;
            $datosF['CDH_BBDD_PASSWD_CLOUD'     ]   = CDH_CMS_DB_PASSWD;
            $datosF['CDH_BBDD_NAME_CLOUD'       ]   = CDH_CMS_DB_NAME;
            $datosF['CDH_BBDD_ENDPOINT_CLOUD'   ]   = $endPointDbWriter;

            #inicio oli 
            /*
            *CDH_ENDPOINT_EFS       = '{CDH_ENDPOINT_EFS}'
            CDH_BBDD_USER_CLOUD     = '{CDH_BBDD_USER_CLOUD}'
            CDH_BBDD_PASSWD_CLOUD   = '{CDH_BBDD_PASSWD_CLOUD}'
            CDH_BBDD_NAME_CLOUD     = '{CDH_BBDD_NAME_CLOUD}'
            CDH_BBDD_ENDPOINT_CLOUD = '{CDH_BBDD_ENDPOINT_CLOUD}'
            */
            //var_dump($endPointDbWriter);die();
            $plantilla = file_get_contents('store/scriptDbLocalToCloudTemplate.sh');
            if($plantilla){
                $search = array('{dominio_sin_punto}',
                               '{dominio_con_punto}',
                               '{nombre_dir_backup}',
                               '{nombre_fichero_sql}',
                               '{region_aws}',
                               '{nombre_cms}',
                               '{version_cms}',
                               '{CDH_ENDPOINT_EFS}',
                               '{CDH_BBDD_USER_CLOUD}',
                               '{CDH_BBDD_PASSWD_CLOUD}',
                               '{CDH_BBDD_NAME_CLOUD}',
                               '{CDH_BBDD_ENDPOINT_CLOUD}',
                               '{CDH_DIR_EFS}',
                               '{CDH_DB_LOCAL_TO_CLOUD_BUCKET_NAME}',
                               '{CDH_DB_LOCAL_TO_CLOUD_BUCKET_TAG_KEY}',
                               '{CDH_DB_LOCAL_TO_CLOUD_BUCKET_TAG_VALUE}'
                                );
                $replace = array(CDH_CMS_DOMINIO_SIN_PUNTO,
                                 CDH_CMS_DOMINIO_CON_PUNTO,
                                 CDH_DATABASE_LOCALDIR_BACKUP,
                                 CDH_DATABASE_LOCALFILE_BACKUP,
                                 CDH_CMS_AWS_REGION,
                                 CDH_CMS_NOMBRE_CMS,
                                 CDH_CMS_VERSION_CMS,
                                 $endPointEfs,
                                 CDH_CMS_DB_USER,
                                 CDH_CMS_DB_PASSWD,
                                 CDH_CMS_DB_NAME,
                                 $endPointDbWriter,
                                 CDH_DIR_EFS,
                                 CDH_DB_LOCAL_TO_CLOUD_BUCKET_NAME,
                                 CDH_DB_LOCAL_TO_CLOUD_BUCKET_TAG_KEY,
                                 CDH_DB_LOCAL_TO_CLOUD_BUCKET_TAG_VALUE
                                );

                                             
                #remplazar los envoltorios por valores reales
                $plantilla = str_replace($search, $replace, $plantilla);

                #intentar crear el fichero sh
                file_put_contents('store/scriptDbLocalToCloud.sh', $plantilla);
                
                
            }
            #fin oli

            /*$datosFKeys      = array_keys  ($datosF);
            $datosFValues    = array_values($datosF);

            $texto   = file_get_contents( CDH_DB_LOCAL_TO_CLOUD_FILE_USER_DATA_TEMPLATE );

            foreach ( $datosF as $key=>$value ) {
                $txt    = $key . "='" . $value . "'";
                $texto = preg_replace( '/'.$key.'/', $txt, $texto, 1, $count  );
            }


            file_put_contents( CDH_DB_LOCAL_TO_CLOUD_FILE_USER_DATA, $texto );*/
            
            return true;

        }

        public function keyPairCreate(){
            $service        = new CDH_awsKeyPair();
            $service        = $service->create( CDH_DB_LOCAL_TO_CLOUD_keyPairCreate, CDH_DB_LOCAL_TO_CLOUD_sshAccessFilePath );
            #return true;
            if( $service == false)
                die ("\nL252 - KEYPAIR no creado\n");
                
            return true;
        }

        #endregion serviceConnect



        #region serviceTransmission
        /**
         * Esta función comprueba si el CMS está activo
         * Si no esta activo solicita al usuario que desactive el CMS
         * No se puede desactivar hasta finalzar el proceso
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
         * zip -r -s 100m prestashop.zip prestashop.sql
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

        /*
        * export base de datos a un archivo .sql en carpeta store/CDH_dbLocal
        */
        private function exportDatabaseToFile(){
            #crear la copia de la base de datos 
            $dump = new Mysqldump('mysql:host='.CDH_CMS_DB_SERVER.';dbname='.
                                  CDH_CMS_DB_NAME , CDH_CMS_DB_USER, CDH_CMS_DB_PASSWD);

            #verificar si la carpeta de destino existe, crearla y hacer el volcado
            if(!file_exists(CDH_CMS_BASE_DIR . '/'.CDH_DATABASE_LOCALDIR_BACKUP)){
                if(mkdir(CDH_CMS_BASE_DIR . '/'.CDH_DATABASE_LOCALDIR_BACKUP, 0755)){
                    $dump->start(CDH_CMS_BASE_DIR . '/'.
                                 CDH_DATABASE_LOCALDIR_BACKUP.'/'.
                                 CDH_DATABASE_LOCALFILE_BACKUP);

                    #verificar si el fichero dump.sql existe en la carpeta store
                    if (file_exists(CDH_CMS_BASE_DIR . '/'.CDH_DATABASE_LOCALDIR_BACKUP.'/'.
                                    CDH_DATABASE_LOCALFILE_BACKUP)) {
                        return true;
                    }
                }
            }

            #crear el volcado si el directorio existe, creado de antes
            $dump->start(CDH_CMS_BASE_DIR . '/'.
                         CDH_DATABASE_LOCALDIR_BACKUP.'/'.
                         CDH_DATABASE_LOCALFILE_BACKUP);

            #verificar si el fichero dump.sql existe en la carpeta store
            if (file_exists(CDH_CMS_BASE_DIR . '/'.CDH_DATABASE_LOCALDIR_BACKUP.'/'.CDH_DATABASE_LOCALFILE_BACKUP)) {
                return true;
            }
            return false;
        }

        /**
         * NO se borra por pena y posibilidad de uso
         * 
         * crear ficheros zip de 100mb, segmentar el fichero dump.sql en particiones de 100mb cada una 
         * 
         */


        private function segmentarBaseDatos($ruta_fichero, $nombre_zip, $numero_lineas_a_leer = 5000){
            #verficar si el fichero a comprimir existe, hacer zip si lo encuentra
            if (file_exists($ruta_fichero)) {

                #abrir el ficher sql
                $f = @fopen($ruta_fichero, "r");
                #error counter
                $error = false;

                #contador_lines leidas 
                $contador_no_lineas = 0;
                #contador de segmentos, se añade al nombre del archivo
                $contador_segmentos = 0;

                #los datos de cada fichero zip se almacenan en esta variable
                $datos_para_zip = '';
                while(!feof($f)){
                    $datos_para_zip .= fgets($f);
                    #aumentar el contador de lineas
                    $contador_no_lineas += 1;

                    #verificar si se ha llegado al numero maximo de lineas a leer
                    if($contador_no_lineas == $numero_lineas_a_leer || feof($f)){
                        #aumentar numero de segmentos
                        $contador_segmentos += 1;

                        #crear un zip con los datos leidos hasta ahora
                        $zip = new ZipArchive();
                        
                        if ($zip->open($nombre_zip .$contador_segmentos. '.zip', ZipArchive::CREATE) == TRUE){
                            $zip->addFromString('temp.txt', $datos_para_zip);
                            $zip->close();

                            #resetear numero de lineas
                            $contador_no_lineas = 0;
                            #resetear los datos para el fichero zip
                            $datos_para_zip = '';
                        }else{
                            $error = true;
                        }
                    }
                }
                fclose($f);

                #verificar los errores
                return $error == true ? false : true;

            }

            return false;
        }

        /*
        * crear fichero texto con la url de ficheros con una terminacion especifica de un carpeta
        * añadir la terminacion deseada SIN el punto, Ej: zip, png, etc
        */
        public function crearFicherTextoConUrl($url_base, $carpeta_de_origen, $ruta_fichero_con_url, $terminacion){
            #verificar si la carpeta existe, y si es una carpta no un fichero
            if (file_exists($carpeta_de_origen) && is_dir($carpeta_de_origen)) {
                #recuperar listado de ficheros de la carpeta que se busca
                $listado_ficheros = @scandir($carpeta_de_origen);

                #si $listado_ficheros es un array iterar y buscar los ficheros por la terminacion deseada
                if (is_array($listado_ficheros)) {
                    $ficheros_encontrados = array(); #array con los fiheros encontrados

                    #iterar por el listado de ficheros buscando los tipos de fichers 
                    foreach($listado_ficheros as $k => $v){
                        $tipo_fichero = pathinfo($v);

                        if ($v['extension'] == $terminacion) {
                            $ficheros_encontrados[] = $v;
                        }
                    }
                    #### fin creacion de array de los archivo encontrados ###


                    # escribir el fichero de texto con la url de cada ficero
                    if (count($ficheros_encontrados) > 0) {
                        #vaciar, crear fichero texto para escribir los url de archivo
                        file_put_contents($ruta_fichero_con_url, "");

                        #escribir el fichero de texto
                        foreach($ficheros_encontrados as $k => $v){
                            file_put_contents($ruta_fichero_con_url, $url_base . $v . "\n", FILE_APPEND);
                        }

                        #verificar que realmente se ha escrito algo en el fichero txt
                        $numero_lineas_escritas = count( explode("\n", file_get_contents($ruta_fichero_con_url)) );
                        if($numero_lineas_escritas > 0){
                            return true; #si se encuentran los lineas de texto devolver true
                        }

                    }
                    #### fin creacion del archivo de texto

                    return false; #si no se encuentran los fichero con la terminacion deseada devolver falso
                }

                return false; #si el listado de fichero del scandir no es un array devolver false
            }

            return false; #si la carpeta no se encuentra devolver falso
        }



        private function copyDatabaseLocalInSqlFile(){
            #exportar la base de datos a un fichero
            $sql_dump = $this->exportDatabaseToFile();
            if (!$sql_dump) {
                echo "\nL512 - error crear fichero ".CDH_DATABASE_LOCALDIR_BACKUP."dump.sql -- copia de la db";
                return false;
            }


            return true;
        }


        private function instanceRun(){

            $instance   = new CDH_awsInstance();
            $bucket     = new CDH_awsBucket();
            $ip         = new CDH_awsInstanceProfile();
            $sg         = new CDH_awsSecurityGroup();
            $subnet     = new CDH_awsSubnet();
            $image      = new CDH_awsImage();
        
            $ipService      = $ip->getServiceByName     (   CDH_DB_LOCAL_TO_CLOUD_createInstanceProfile['InstanceProfileName'] );
            $ipArn          = $ipService['Arn'];

            $sgService      = $sg->getServiceByName     (   CDH_DB_LOCAL_TO_CLOUD_createSecurityGroup  ['GroupName']  );
            $sgId           = $sgService['GroupId'];

            $subnetService  = $subnet->getServiceByName (   $this->getSubnetPublic0Name()       );
            $subnetId       = $subnetService['SubnetId'];


            $instance->deleteByTagName( CDH_DB_LOCAL_TO_CLOUD_instanceRun['TagSpecifications'][0]['Tags'][0]['Value'] );


            $CDH_IMAGE_INSTANCE                                         = CDH_DB_LOCAL_TO_CLOUD_instanceRun;
            $CDH_IMAGE_INSTANCE['IamInstanceProfile']['Arn']            = $ipService['Arn'];
            $CDH_IMAGE_INSTANCE['NetworkInterfaces'][0]['Groups'][0]    = $sgId;
            $CDH_IMAGE_INSTANCE['NetworkInterfaces'][0]['SubnetId']     = $subnetId;
            $CDH_IMAGE_INSTANCE['ImageId']                              = CDH_IMAGE_INSTANCE['ImageId'];
            #$CDH_IMAGE_INSTANCE['UserData']                             = base64_encode( file_get_contents( CDH_IMAGE_FILE_USER_DATA));
            
            //var_export( $CDH_IMAGE_INSTANCE );
            #die("\nL356\n");
            #return true;
            
            $instances  = $instance->create( $CDH_IMAGE_INSTANCE );
            echo "\nL316\n";
            if( $instances == false || !isset($instances[0]['InstanceId']) )
                return false;
                
            #$instanceId = $instances[0]['InstanceId'];
            
            $instanceName = $CDH_IMAGE_INSTANCE['TagSpecifications'][0]['Tags'][0]['Value'];

            $instanceId = $instance->getIdByName( $instanceName );

            ####################

            $name   = CDH_DB_LOCAL_TO_CLOUD_BUCKET_NAME;
            $key    = CDH_DB_LOCAL_TO_CLOUD_BUCKET_TAG_KEY;
            $value  = CDH_DB_LOCAL_TO_CLOUD_BUCKET_TAG_VALUE; 
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

        public function instanceTerminate(){
            $instance = new CDH_awsInstance();
            return $instance->deleteByTagName( CDH_DB_LOCAL_TO_CLOUD_instanceRun['TagSpecifications'][0]['Tags'][0]['Value'] );

        }

    

        public function keyPairDelete(){
            $keyPair = new CDH_awsKeyPair();
            if( !$keyPair->delete( CDH_DB_LOCAL_TO_CLOUD_keyPairCreate['KeyName'] )  ){
                echo "\nL411 - No se ha podido borrar el KeyPair\n";
            }else{
                echo "\nL413 - Se ha podido borrar el KeyPair\n";
            }

            return true;
        }

        public function securityGroupDelete(){
            $sg     =  new CDH_awsSecurityGroup();
            $sgId   =  $sg->getIdByName( CDH_DB_LOCAL_TO_CLOUD_createSecurityGroup['GroupName'] );
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


            $policyArn = $policy->getArnByName(CDH_DB_LOCAL_TO_CLOUD_createPolicy['PolicyName']);

        
            $detachRolePolicy = [
                'PolicyArn' => $policyArn, // REQUIRED
                'RoleName' => CDH_DB_LOCAL_TO_CLOUD_createRole['RoleName'], // REQUIRED
            ];


            $removeRoleFromInstanceProfile = [
                'InstanceProfileName'   => CDH_DB_LOCAL_TO_CLOUD_createRole['RoleName'], // REQUIRED
                'RoleName'              => CDH_DB_LOCAL_TO_CLOUD_createRole['RoleName'], // REQUIRED
            ];


            $role->detachRolePolicy             ( $detachRolePolicy                 );
            $role->removeRoleFromInstanceProfile( $removeRoleFromInstanceProfile    );
            $role->delete( CDH_DB_LOCAL_TO_CLOUD_createRole['RoleName']             );
            #$policy->delete( CDH_DB_LOCAL_TO_CLOUD_createPolicy['PolicyName']       );
            return true;
            
        }

        public function policyDelete(){
            $policy             = new CDH_awsPolicy();
            $policy->delete( CDH_DB_LOCAL_TO_CLOUD_createPolicy['PolicyName']       );
            return true;
        }


        #endregion serviceEnding

    }





?>
