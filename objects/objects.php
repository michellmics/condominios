<?php


    include '../../phpMailer/src/PHPMailer.php';
    include '../../phpMailer/src/SMTP.php';
    include '../../phpMailer/src/Exception.php'; 

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;


	class SITE_ADMIN 
	{
        //declaração de variaveis 
        public $pdo;
        public $ARRAY_SITEINFO;
        public $ARRAY_LISTAINFO;
        public $ARRAY_USERINFOLIST;
        public $ARRAY_USERINFOBYID;
        public $ARRAY_DESCEMPRESAINFO;
        public $ARRAY_ALERTA;
        public $ARRAY_PARAMETERINFO;
        public $ARRAY_MORADORINFO;
        public $ARRAY_LISTAMORADORESINFO;
        public $ARRAY_FOOTERPUBLISHINFO;
        public $ARRAY_LOGINFO;
        public $ARRAY_POPUPPUBLISHINFO;
        public $ARRAY_CONVIDADOINFO;
        public $ARRAY_RELINFO;
        public $ARRAY_ENCOMENDAINFO;
        public $ARRAY_MENSAGENSINFO;
        public $ARRAY_TOKENINFO;
        public $WHATSAPP_TOKEN;
        public $WHATSAPP_SID;
        public $configPath;



        function __construct() {
            // Usando __DIR__ para obter o diretório atual e construir o caminho relativo
            $this->configPath = __DIR__ . '/../../config.cfg';
        }

        function conexao()
        {
            if (!file_exists($this->configPath)) {
                die("Erro: Arquivo de configuração não encontrado.");
            }

            $configContent = parse_ini_file($this->configPath, true);  // true para usar seções

            if (!$configContent) {
                die("Erro: Não foi possível ler o arquivo de configuração.");
            }

            $cpanelUser = $configContent['CPANEL']['usuario'];
            $host = $configContent['DATA DB']['host'];
            $dbname = $cpanelUser . "_" . $configContent['DATA DB']['dbname'];
            $user = $cpanelUser . "_" . $configContent['DATA DB']['user'];
            $pass = $configContent['DATA DB']['pass'];
            $this->WHATSAPP_TOKEN = $configContent['WHATSAPP']['token'];
            $this->WHATSAPP_SID = $configContent['WHATSAPP']['sid'];

            try {
                $this->pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die("Erro na conexão: " . $e->getMessage());
            }
        }

        public function getAvaliacoesByCategoria($PDS_DCCATEGORIA)
        {          
                // Verifica se a conexão já foi estabelecida
                if(!$this->pdo){$this->conexao();}
            
            try{           
                $sql = "SELECT DISTINCT(PDS_DCNOME), PDS_IDPRESTADOR_SERVICO, PDS_DCTELEFONE, PDS_DCCIDADE FROM VW_AVALIACAO_PRESTADOR WHERE PDS_DCCATEGORIA = :PDS_DCCATEGORIA";

                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':PDS_DCCATEGORIA', $PDS_DCCATEGORIA, PDO::PARAM_STR);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                return ["error" => $e->getMessage()];
            }       
        }
        public function getAvaliacoesByPrestador($PDS_IDPRESTADOR_SERVICO)
        {          
                // Verifica se a conexão já foi estabelecida
                if(!$this->pdo){$this->conexao();}
            
            try{           
                $sql = "SELECT * FROM VW_AVALIACAO_PRESTADOR WHERE PDS_IDPRESTADOR_SERVICO = :PDS_IDPRESTADOR_SERVICO AND APS_NMNOTA IS NOT NULL ORDER BY APS_DTAVAL DESC";

                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':PDS_IDPRESTADOR_SERVICO', $PDS_IDPRESTADOR_SERVICO, PDO::PARAM_STR);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                return ["error" => $e->getMessage()];
            }       
        }

        public function getAvaliacoesNotasAVGByPrestador($PDS_IDPRESTADOR_SERVICO)
        {          
                // Verifica se a conexão já foi estabelecida
                if(!$this->pdo){$this->conexao();}
            
            try{           
                $sql = "SELECT AVG(APS_NMNOTA) as AVG FROM VW_AVALIACAO_PRESTADOR WHERE PDS_IDPRESTADOR_SERVICO = :PDS_IDPRESTADOR_SERVICO";

                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':PDS_IDPRESTADOR_SERVICO', $PDS_IDPRESTADOR_SERVICO, PDO::PARAM_STR);
                $stmt->execute();
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                return ["error" => $e->getMessage()];
            }       
        }

        // Função para gerar um novo token de acesso
        public function gerarToken($userId) {
            $chaveSecreta = "mcodemaze!4795condominio$#@!!@#$"; // chave secreta forte
            $dados = [
                "user_id" => $userId,
                "exp" => time() + (30 * 24 * 60 * 60) // Expira em 30 dias
            ];
            return base64_encode(json_encode($dados) . "." . hash_hmac('sha256', json_encode($dados), $chaveSecreta));
        }

        public function whatsapp($msg,$telefone)
        {
            // URL do script que processa os dados
            $url = 'https://prqdashortensias.com.br/pages/whatsapp/send_message.php';

            // Dados que serão enviados no POST
            $data = [
                'telefone' => "$telefone", // Substitua pelo telefone
                'message' => "$msg"
            ];

            // Inicializar o cURL
            $ch = curl_init($url);

            // Configurar opções do cURL
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json', // Informar que os dados estão em JSON
                'Content-Length: ' . strlen(json_encode($data))
            ]);

            // Executar a solicitação
            $response = curl_exec($ch);

            // Verificar erros
            if (curl_errno($ch)) {
                echo 'Erro no cURL: ' . curl_error($ch);
            } else {
                echo 'Resposta do servidor: ' . $response;
            }

            // Fechar o cURL
            curl_close($ch);
        }
        
        public function stmtToArray($stmtFunction)
		{		
			$stmtFunction_array = array();							
			while ($row = $stmtFunction->fetch(PDO::FETCH_ASSOC))
			{	
				array_push($stmtFunction_array, $row);	
			}		
	
			return $stmtFunction_array;
		}	

        public function getEncomendaMoradorInfo($USU_IDUSUARIO)
        {          
                // Verifica se a conexão já foi estabelecida
                if(!$this->pdo){$this->conexao();}
            
            try{           
                $sql = "SELECT ENC.ENC_IDENCOMENDA, USU.USU_DCAPARTAMENTO, ENC.ENC_STENCOMENDA, ENC.ENC_DTENTREGA_PORTARIA, ENC.ENC_DTENTREGA_MORADOR, ENC.ENC_DCOBSERVACAO, ENC.ENC_STENTREGA_MORADOR
                        FROM ENC_ENCOMENDA ENC 
                        INNER JOIN USU_USUARIO USU ON (USU.USU_IDUSUARIO = ENC.USU_IDUSUARIO)
                        WHERE ENC.ENC_STENCOMENDA = 'DISPONIVEL' AND ENC.USU_IDUSUARIO = :USU_IDUSUARIO AND (ENC.ENC_STENTREGA_MORADOR != 'ENTREGUE' OR ENC.ENC_STENTREGA_MORADOR IS NULL)
                        ORDER BY ENC_DTENTREGA_PORTARIA DESC
                        LIMIT 10";

                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':USU_IDUSUARIO', $USU_IDUSUARIO, PDO::PARAM_STR);
                $stmt->execute();
                $this->ARRAY_ENCOMENDAINFO = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                return ["error" => $e->getMessage()];
            }       
        }

        public function getTokenInfo($USU_DCTOKEN)
        {          
                // Verifica se a conexão já foi estabelecida
                if(!$this->pdo){$this->conexao();}
            
            try{           
                $sql = "SELECT USU_DCTOKEN FROM USU_USUARIO WHERE USU_DCTOKEN = :USU_DCTOKEN";

                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':USU_DCTOKEN', $USU_DCTOKEN, PDO::PARAM_STR);
                $stmt->execute();
                $this->ARRAY_TOKENINFO = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                return ["error" => $e->getMessage()];
            }       
        }

        public function getEncomendaPortariaInfo()
        {          
                // Verifica se a conexão já foi estabelecida
                if(!$this->pdo){$this->conexao();}
            
            try{           
                $sql = "SELECT ENC.ENC_IDENCOMENDA, USU.USU_DCNOME, USU.USU_DCAPARTAMENTO, ENC.ENC_STENCOMENDA, ENC.ENC_DTENTREGA_PORTARIA, ENC.ENC_DTENTREGA_MORADOR, ENC.ENC_DCOBSERVACAO, ENC.ENC_STENTREGA_MORADOR
                        FROM ENC_ENCOMENDA ENC 
                        INNER JOIN USU_USUARIO USU ON (USU.USU_IDUSUARIO = ENC.USU_IDUSUARIO)
                        ORDER BY ENC_DTENTREGA_PORTARIA DESC";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                $this->ARRAY_ENCOMENDAINFO = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                return ["error" => $e->getMessage()];
            }       
        }

        public function getParameterInfo()
        {          
                // Verifica se a conexão já foi estabelecida
                if(!$this->pdo){$this->conexao();}
            
            try{           
                $sql = "SELECT CFG_DCPARAMETRO, 
                                CFG_DCVALOR
                                FROM CFG_CONFIGURACAO";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                $this->ARRAY_PARAMETERINFO = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                return ["error" => $e->getMessage()];
            }       
        }

        public function getListaMoradoresInfo()
        {          
                // Verifica se a conexão já foi estabelecida
                if(!$this->pdo){$this->conexao();}
            
            try{           
                $sql = "SELECT *
                                FROM USU_USUARIO
                                ORDER BY USU_DCAPARTAMENTO ASC";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                $this->ARRAY_LISTAMORADORESINFO = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                return ["error" => $e->getMessage()];
            }          
        }

        public function getMoradoresByApInfo($USU_DCAPARTAMENTO)
        {          
                // Verifica se a conexão já foi estabelecida
                if(!$this->pdo){$this->conexao();}
            
            try{           
                $sql = "SELECT *
                                FROM USU_USUARIO
                                WHERE USU_DCAPARTAMENTO = :USU_DCAPARTAMENTO ";

                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':USU_DCAPARTAMENTO', $USU_DCAPARTAMENTO, PDO::PARAM_STR);
                $stmt->execute();
                $this->ARRAY_LISTAMORADORESINFO = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                return ["error" => $e->getMessage()];
            }          
        }

        public function getListaMensagensSugestoesInfo()
        {          
                // Verifica se a conexão já foi estabelecida
                if(!$this->pdo){$this->conexao();}
            
            try{           
                $sql = "SELECT *
                                FROM REC_RECLAMACAO
                                ORDER BY REC_DTDATA DESC
                                LIMIT 20";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                $this->ARRAY_MENSAGENSINFO = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                return ["error" => $e->getMessage()];
            }          
        }

        public function getListaInfo($USU_IDUSUARIO)
        {          
                // Verifica se a conexão já foi estabelecida
                if(!$this->pdo){$this->conexao();}
            
            try{           
                $sql = "SELECT * FROM LIS_LISTACONVIDADOS WHERE USU_IDUSUARIO = $USU_IDUSUARIO ORDER BY LIS_DCNOME ASC";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                $this->ARRAY_LISTAINFO = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                return ["error" => $e->getMessage()];
            }          
        }

        public function getListaInfoByMorador($USU_IDUSUARIO)
        {          
                // Verifica se a conexão já foi estabelecida
                if(!$this->pdo){$this->conexao();}
            
            try{           
                $sql = "SELECT * FROM LIS_LISTACONVIDADOS WHERE USU_IDUSUARIO = $USU_IDUSUARIO AND LIS_STSTATUS = 'ATIVO' ORDER BY LIS_DCNOME ASC";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                $this->ARRAY_LISTAINFO = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                return ["error" => $e->getMessage()];
            }          
        }

        public function getConvidadoById($LIS_IDLISTACONVIDADOS)
        {          
                // Verifica se a conexão já foi estabelecida
                if(!$this->pdo){$this->conexao();}
            
            try{           
                $sql = "SELECT * FROM LIS_LISTACONVIDADOS WHERE LIS_IDLISTACONVIDADOS = :LIS_IDLISTACONVIDADOS";

                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':LIS_IDLISTACONVIDADOS', $LIS_IDLISTACONVIDADOS, PDO::PARAM_STR);
                $stmt->execute();
                $this->ARRAY_CONVIDADOINFO = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                return ["error" => $e->getMessage()];
            }          
        }

        public function getMoradorById($USU_DCAPARTAMENTO)
        {          
                // Verifica se a conexão já foi estabelecida
                if(!$this->pdo){$this->conexao();}
            
            try{           
                $sql = "SELECT * FROM USU_USUARIO WHERE USU_DCAPARTAMENTO = :USU_DCAPARTAMENTO";

                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':USU_DCAPARTAMENTO', $USU_DCAPARTAMENTO, PDO::PARAM_STR);
                $stmt->execute();
                $this->ARRAY_USERINFOBYID = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                return ["error" => $e->getMessage()];
            }          
        }

        public function getMoradorByUserId($USU_IDUSUARIO)
        {          
                // Verifica se a conexão já foi estabelecida
                if(!$this->pdo){$this->conexao();}
            
            try{           
                $sql = "SELECT * FROM USU_USUARIO WHERE USU_IDUSUARIO = :USU_IDUSUARIO";

                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':USU_IDUSUARIO', $USU_IDUSUARIO, PDO::PARAM_STR);
                $stmt->execute();
                $this->ARRAY_USERINFOBYID = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                return ["error" => $e->getMessage()];
            }          
        }

        public function insertLogInfo($LOG_DCTIPO, $LOG_DCMSG, $LOG_DCUSUARIO = 'N/A', $LOG_DCAPARTAMENTO = 'N/A', $LOG_DCCODIGO = 'N/A')
        {       
            // Verifica se a conexão já foi estabelecida
            if (!$this->pdo) {
                $this->conexao();
            }
            $now = new DateTime(); 
            $DATA = $now->format('Y-m-d H:i:s');

            try {
                $sql = "INSERT INTO LOG_LOGSISTEMA 
                        (LOG_DCTIPO, LOG_DCMSG, LOG_DCUSUARIO, LOG_DCAPARTAMENTO, LOG_DTLOG, LOG_DCCODIGO) 
                        VALUES (:LOG_DCTIPO, :LOG_DCMSG, :LOG_DCUSUARIO, :LOG_DCAPARTAMENTO, :LOG_DTLOG, :LOG_DCCODIGO)";

                $stmt = $this->pdo->prepare($sql);
            
                // Liga os parâmetros aos valores
                $stmt->bindParam(':LOG_DCTIPO', $LOG_DCTIPO, PDO::PARAM_STR);
                $stmt->bindParam(':LOG_DCMSG', $LOG_DCMSG, PDO::PARAM_STR);
                $stmt->bindParam(':LOG_DCUSUARIO', $LOG_DCUSUARIO, PDO::PARAM_STR);
                $stmt->bindParam(':LOG_DCAPARTAMENTO', $LOG_DCAPARTAMENTO, PDO::PARAM_STR);
                $stmt->bindParam(':LOG_DTLOG', $DATA, PDO::PARAM_STR);
                $stmt->bindParam(':LOG_DCCODIGO', $LOG_DCCODIGO, PDO::PARAM_STR);
            
                $stmt->execute();
           
            } catch (PDOException $e) {
                // Captura e retorna o erro
                return ["error" => $e->getMessage()];
            }
        }

        public function gravarMensagemSugestao($REC_DCMSG)
        {       
            // Verifica se a conexão já foi estabelecida
            if (!$this->pdo) {
                $this->conexao();
            }
            $now = new DateTime(); 
            $DATA = $now->format('Y-m-d H:i:s');

            try {
                $sql = "INSERT INTO REC_RECLAMACAO 
                        (REC_DCMSG, REC_DTDATA) 
                        VALUES (:REC_DCMSG, :REC_DTDATA)";

                $stmt = $this->pdo->prepare($sql);
            
                // Liga os parâmetros aos valores
                $stmt->bindParam(':REC_DCMSG', $REC_DCMSG, PDO::PARAM_STR);
                $stmt->bindParam(':REC_DTDATA', $DATA, PDO::PARAM_STR);
            
                $stmt->execute();
           
            } catch (PDOException $e) {
                // Captura e retorna o erro
                return ["error" => $e->getMessage()];
            }
        }

        public function insertPacoteInfo($USU_IDUSUARIO, $ENC_DCOBSERVACAO)
        {       
            // Verifica se a conexão já foi estabelecida
            if (!$this->pdo) {
                $this->conexao();
            }
            $now = new DateTime(); 
            $DATA = $now->format('Y-m-d H:i:s');
            $ENC_STENCOMENDA = "INDISPONIVEL";

            $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';                    
            $codigo = substr(str_shuffle($caracteres), 0, 5);

            try {
                $sql = "INSERT INTO ENC_ENCOMENDA 
                        (ENC_IDENCOMENDA, ENC_DCOBSERVACAO, USU_IDUSUARIO, ENC_DTENTREGA_PORTARIA, ENC_STENCOMENDA) 
                        VALUES (:ENC_IDENCOMENDA, :ENC_DCOBSERVACAO, :USU_IDUSUARIO, :ENC_DTENTREGA_PORTARIA, :ENC_STENCOMENDA)";

                $stmt = $this->pdo->prepare($sql);
            
                // Liga os parâmetros aos valores
                $stmt->bindParam(':USU_IDUSUARIO', $USU_IDUSUARIO, PDO::PARAM_STR);
                $stmt->bindParam(':ENC_DCOBSERVACAO', $ENC_DCOBSERVACAO, PDO::PARAM_STR);
                $stmt->bindParam(':ENC_DTENTREGA_PORTARIA', $DATA, PDO::PARAM_STR);
                $stmt->bindParam(':ENC_STENCOMENDA', $ENC_STENCOMENDA, PDO::PARAM_STR);
                $stmt->bindParam(':ENC_IDENCOMENDA', $codigo, PDO::PARAM_STR);
            
                $stmt->execute();

                return $codigo;
           
            } catch (PDOException $e) {
                // Captura e retorna o erro
                return  $e->getMessage();
            }
        }

        public function getPopupImagePublish()
        {          
                // Verifica se a conexão já foi estabelecida
                if(!$this->pdo){$this->conexao();}
            
            try{           
                $sql = "SELECT PUB_DCIMG, PUB_DCLINK  FROM PUB_PUBLICIDADE
                        WHERE PUB_STSTATUS = 'ATIVA' AND PUB_DCTIPO LIKE '%IMAGEM%'";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                $this->ARRAY_POPUPPUBLISHINFO = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                return ["error" => $e->getMessage()];
            }          
        }

        public function getFooterPublish()
        {          
                // Verifica se a conexão já foi estabelecida
                if(!$this->pdo){$this->conexao();}
            
            try{           
                $sql = "SELECT PUB_DCDESC  FROM PUB_PUBLICIDADE
                        WHERE PUB_STSTATUS = 'ATIVA' AND PUB_DCTIPO LIKE '%TEXTO%'";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                $this->ARRAY_FOOTERPUBLISHINFO = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                return ["error" => $e->getMessage()];
            }          
        }

        public function checkPubliExisInfo($ID)
        {          
            // Verifica se a conexão já foi estabelecida
            if (!$this->pdo) {
                $this->conexao();
            }
        
            try {           
                $sql = "SELECT 1 FROM PUB_PUBLICIDADE WHERE MKT_IDMKTPUBLICIDADE = :MKT_IDMKTPUBLICIDADE";
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':MKT_IDMKTPUBLICIDADE', $ID, PDO::PARAM_STR);
                $stmt->execute();
        
                // Verifica se encontrou algum registro
                return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
            } catch (PDOException $e) {
                return ["error" => $e->getMessage()];
            }
        }

        public function updatePubliInfo($PUB_DTINI, $PUB_DTFIM, $PUB_DCCLIENTEORIG, $PUB_STSTATUS, $MKT_IDMKTPUBLICIDADE, $PUB_DCIMG, $PUB_DCDESC, $PUB_DCTIPO, $PUB_DCLINK)
        {                            
            // Verifica se a conexão já foi estabelecida
            if (!$this->pdo) {
                $this->conexao();
            }

            try {

                $sql = "UPDATE PUB_PUBLICIDADE 
                        SET
                        PUB_DTINI = :PUB_DTINI,
                        PUB_DTFIM = :PUB_DTFIM,
                        PUB_DCCLIENTEORIG = :PUB_DCCLIENTEORIG,
                        PUB_STSTATUS = :PUB_STSTATUS,
                        PUB_DCIMG = :PUB_DCIMG,
                        PUB_DCDESC = :PUB_DCDESC,
                        PUB_DCTIPO = :PUB_DCTIPO,
                        PUB_DCLINK = :PUB_DCLINK
                        WHERE MKT_IDMKTPUBLICIDADE = :MKT_IDMKTPUBLICIDADE";                       

                $stmt = $this->pdo->prepare($sql);
            
                // Liga os parâmetros aos valores
                $stmt->bindParam(':PUB_DTINI', $PUB_DTINI, PDO::PARAM_STR);
                $stmt->bindParam(':PUB_DTFIM', $PUB_DTFIM, PDO::PARAM_STR);
                $stmt->bindParam(':PUB_DCCLIENTEORIG', $PUB_DCCLIENTEORIG, PDO::PARAM_STR);
                $stmt->bindParam(':PUB_STSTATUS', $PUB_STSTATUS, PDO::PARAM_STR);
                $stmt->bindParam(':MKT_IDMKTPUBLICIDADE', $MKT_IDMKTPUBLICIDADE, PDO::PARAM_STR);
                $stmt->bindParam(':PUB_DCIMG', $PUB_DCIMG, PDO::PARAM_STR);
                $stmt->bindParam(':PUB_DCDESC', $PUB_DCDESC, PDO::PARAM_STR); 
                $stmt->bindParam(':PUB_DCTIPO', $PUB_DCTIPO, PDO::PARAM_STR); 
                $stmt->bindParam(':PUB_DCLINK', $PUB_DCLINK, PDO::PARAM_STR);

            
                $stmt->execute();
           

                if($PUB_STSTATUS == "ATIVA")
                {
                    return "PUBLICADO";
                } 
                else
                    {
                        return "PENDENTE";
                    }
                    
            } catch (PDOException $e) {
                // Captura e retorna o erro
                return "ERRO: Não foi possível atualizar a publicidade.";
            }
        }

        public function insertPubliInfo($PUB_DTINI, $PUB_DTFIM, $PUB_DCCLIENTEORIG, $PUB_STSTATUS, $MKT_IDMKTPUBLICIDADE, $PUB_DCIMG, $PUB_DCDESC, $PUB_DCTIPO, $PUB_DCLINK)
        {                            
            // Verifica se a conexão já foi estabelecida
            if (!$this->pdo) {
                $this->conexao();
            }

            try {

                $sql = "INSERT INTO PUB_PUBLICIDADE 
                        (PUB_DTINI, PUB_DTFIM, PUB_DCCLIENTEORIG, PUB_STSTATUS, MKT_IDMKTPUBLICIDADE, PUB_DCIMG, PUB_DCDESC, PUB_DCTIPO, PUB_DCLINK) 
                        VALUES (:PUB_DTINI, :PUB_DTFIM, :PUB_DCCLIENTEORIG, :PUB_STSTATUS, :MKT_IDMKTPUBLICIDADE, :PUB_DCIMG, :PUB_DCDESC, :PUB_DCTIPO, :PUB_DCLINK)";

                $stmt = $this->pdo->prepare($sql);
            
                // Liga os parâmetros aos valores
                $stmt->bindParam(':PUB_DTINI', $PUB_DTINI, PDO::PARAM_STR);
                $stmt->bindParam(':PUB_DTFIM', $PUB_DTFIM, PDO::PARAM_STR);
                $stmt->bindParam(':PUB_DCCLIENTEORIG', $PUB_DCCLIENTEORIG, PDO::PARAM_STR);
                $stmt->bindParam(':PUB_STSTATUS', $PUB_STSTATUS, PDO::PARAM_STR);
                $stmt->bindParam(':MKT_IDMKTPUBLICIDADE', $MKT_IDMKTPUBLICIDADE, PDO::PARAM_STR);
                $stmt->bindParam(':PUB_DCIMG', $PUB_DCIMG, PDO::PARAM_STR);
                $stmt->bindParam(':PUB_DCDESC', $PUB_DCDESC, PDO::PARAM_STR);
                $stmt->bindParam(':PUB_DCTIPO', $PUB_DCTIPO, PDO::PARAM_STR); 
                $stmt->bindParam(':PUB_DCLINK', $PUB_DCLINK, PDO::PARAM_STR);

            
                $stmt->execute();

                if($PUB_STSTATUS == "ATIVA")
                {
                    return "PUBLICADO";
                } 
                else
                    {
                        return "PENDENTE";
                    }

                        

            } catch (PDOException $e) {
                // Captura e retorna o erro
                return "ERRO: Não foi possível inserir a publicidade.";
            }
        }

        public function insertVisitListaInfo($LIS_DCNOME, $USU_IDUSUARIO, $LIS_DCDOCUMENTO, $LIS_STSTATUS)
        {       
            // Verifica se a conexão já foi estabelecida
            if (!$this->pdo) {
                $this->conexao();
            }

            $now = new DateTime(); 
            $DATA = $now->format('Y-m-d H:i:s');

            try {
                $sql = "INSERT INTO LIS_LISTACONVIDADOS 
                        (LIS_DCNOME, USU_IDUSUARIO, LIS_DCDOCUMENTO, LIS_DTCADASTRO, LIS_STSTATUS) 
                        VALUES (:LIS_DCNOME, :USU_IDUSUARIO, :LIS_DCDOCUMENTO, :LIS_DTCADASTRO, :LIS_STSTATUS)";

                $stmt = $this->pdo->prepare($sql);
            
                // Liga os parâmetros aos valores
                $stmt->bindParam(':LIS_DCNOME', $LIS_DCNOME, PDO::PARAM_STR);
                $stmt->bindParam(':USU_IDUSUARIO', $USU_IDUSUARIO, PDO::PARAM_STR);
                $stmt->bindParam(':LIS_DCDOCUMENTO', $LIS_DCDOCUMENTO, PDO::PARAM_STR);
                $stmt->bindParam(':LIS_DTCADASTRO', $DATA, PDO::PARAM_STR);
                $stmt->bindParam(':LIS_STSTATUS', $LIS_STSTATUS, PDO::PARAM_STR);
                
            
                $stmt->execute();
            
                // Retorna uma mensagem de sucesso (opcional)
                return ["success" => "Visitante cadastrado com sucesso."];
            } catch (PDOException $e) {
                // Captura e retorna o erro
                return ["error" => $e->getMessage()];
            }
        }

        public function updateVisitListaInfo($LIS_DCNOME, $LIS_DCDOCUMENTO, $LIS_STSTATUS, $LIS_IDLISTACONVIDADOS)
        {       
            // Verifica se a conexão já foi estabelecida
            if (!$this->pdo) {
                $this->conexao();
            }

            try {
                $sql = "UPDATE LIS_LISTACONVIDADOS 
                        SET LIS_DCNOME = :LIS_DCNOME,
                        LIS_DCDOCUMENTO = :LIS_DCDOCUMENTO,
                        LIS_STSTATUS = :LIS_STSTATUS
                        WHERE LIS_IDLISTACONVIDADOS = :LIS_IDLISTACONVIDADOS";

                $stmt = $this->pdo->prepare($sql);
            
                // Liga os parâmetros aos valores
                $stmt->bindParam(':LIS_DCNOME', $LIS_DCNOME, PDO::PARAM_STR);
                $stmt->bindParam(':LIS_IDLISTACONVIDADOS', $LIS_IDLISTACONVIDADOS, PDO::PARAM_STR);
                $stmt->bindParam(':LIS_DCDOCUMENTO', $LIS_DCDOCUMENTO, PDO::PARAM_STR);
                $stmt->bindParam(':LIS_STSTATUS', $LIS_STSTATUS, PDO::PARAM_STR);
                
                $stmt->execute();
            
                // Retorna uma mensagem de sucesso (opcional)
                return ["success" => "Visitante atualizado com sucesso."];
            } catch (PDOException $e) {
                // Captura e retorna o erro
                return ["error" => $e->getMessage()];
            }
        }

        public function notifyEmail($SUBJECT, $MSG, $HOST)
        {
            $this->getParameterInfo();

            foreach($this->ARRAY_PARAMETERINFO as $value)
            {
                if($value["CFG_DCPARAMETRO"] == "EMAIL_ALERTAS")
                {
                    $emailTo = $value["CFG_DCVALOR"];
                }
            } 

            // Configurações do e-mail
            $to = $emailTo; 
            $subject = "ATENÇÃO: $SUBJECT";
            $body = "$MSG\n";

            // Adiciona cabeçalhos para o e-mail
            $headers = "From: no-reply@$HOST\r\n";
            $headers .= "Reply-To: no-reply@$HOST\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n"; // Define a codificação como UTF-8
            $headers .= "MIME-Version: 1.0\r\n";
            
            mail($to, $subject, $body, $headers);        
        }

        public function updateCheckboxConvidados($LIS_IDLISTACONVIDADOS, $LIS_STSTATUS)
        {
            // Verifica se a conexão já foi estabelecida
            if (!$this->pdo) {
                $this->conexao();
            }

            try
            {         
                $sql = "UPDATE LIS_LISTACONVIDADOS SET LIS_STSTATUS = :LIS_STSTATUS WHERE LIS_IDLISTACONVIDADOS = :LIS_IDLISTACONVIDADOS";
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':LIS_IDLISTACONVIDADOS', $LIS_IDLISTACONVIDADOS, PDO::PARAM_STR);
                $stmt->bindParam(':LIS_STSTATUS', $LIS_STSTATUS, PDO::PARAM_STR); 
                $stmt->execute();     

                return ["success" => "Visitante atualizado com sucesso."];

            } catch (PDOException $e) {
                // Captura e retorna o erro
                return ["error" => $e->getMessage()];
            }
        }

        public function updateCheckboxEncomendasMorador($ENC_IDENCOMENDA, $ENC_STENTREGA_MORADOR)
        {
            // Verifica se a conexão já foi estabelecida
            if (!$this->pdo) {
                $this->conexao();
            }

            try
            {         
                $sql = "UPDATE ENC_ENCOMENDA SET ENC_STENTREGA_MORADOR = :ENC_STENTREGA_MORADOR WHERE ENC_IDENCOMENDA = :ENC_IDENCOMENDA";
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':ENC_IDENCOMENDA', $ENC_IDENCOMENDA, PDO::PARAM_STR);
                $stmt->bindParam(':ENC_STENTREGA_MORADOR', $ENC_STENTREGA_MORADOR, PDO::PARAM_STR); 
                $stmt->execute();     

                                //--------------------LOG----------------------//
                                $LOG_DCTIPO = "ENCOMENDA";
                                $LOG_DCMSG = "Encomenda com id $ENC_IDENCOMENDA foi alterada seu status para $ENC_STENTREGA_MORADOR";
                                $LOG_DCUSUARIO = "MORADOR";
                                $LOG_DCCODIGO = $ENC_IDENCOMENDA;
                                $LOG_DCAPARTAMENTO = "";
                                $this->insertLogInfo($LOG_DCTIPO, $LOG_DCMSG, $LOG_DCUSUARIO, $LOG_DCAPARTAMENTO, $LOG_DCCODIGO);
                                //--------------------LOG----------------------//

                return ["success" => "Encomenda atualizada com sucesso."];

            } catch (PDOException $e) {
                // Captura e retorna o erro
                return ["error" => $e->getMessage()];
            }
        }

        public function insertUserInfo($USU_DCEMAIL, $USU_DCNOME, $USU_DCBLOCO, $USU_DCAPARTAMENTO, $USU_DCNIVEL, $USU_DCSENHA, $USU_DCTELEFONE)
        {       
            // Verifica se a conexão já foi estabelecida
            if (!$this->pdo) {
                $this->conexao();
            }
            $now = new DateTime(); 
            $DATA = $now->format('Y-m-d H:i:s');

            try {
                $sql = "INSERT INTO USU_USUARIO 
                        (USU_DCEMAIL, USU_DCNOME, USU_DCBLOCO, USU_DCAPARTAMENTO, USU_DCNIVEL, USU_DCSENHA, USU_DTCADASTRO, USU_DCTELEFONE) 
                        VALUES (:USU_DCEMAIL, :USU_DCNOME, :USU_DCBLOCO, :USU_DCAPARTAMENTO, :USU_DCNIVEL, :USU_DCSENHA, :USU_DTCADASTRO, :USU_DCTELEFONE)";

                $stmt = $this->pdo->prepare($sql);
            
                // Liga os parâmetros aos valores
                $stmt->bindParam(':USU_DCEMAIL', $USU_DCEMAIL, PDO::PARAM_STR);
                $stmt->bindParam(':USU_DCNOME', $USU_DCNOME, PDO::PARAM_STR);
                $stmt->bindParam(':USU_DCBLOCO', $USU_DCBLOCO, PDO::PARAM_STR);
                $stmt->bindParam(':USU_DCAPARTAMENTO', $USU_DCAPARTAMENTO, PDO::PARAM_STR);
                $stmt->bindParam(':USU_DCNIVEL', $USU_DCNIVEL, PDO::PARAM_STR);
                $stmt->bindParam(':USU_DCSENHA', $USU_DCSENHA, PDO::PARAM_STR);
                $stmt->bindParam(':USU_DTCADASTRO', $DATA, PDO::PARAM_STR); 
                $stmt->bindParam(':USU_DCTELEFONE', $USU_DCTELEFONE, PDO::PARAM_STR);
                $stmt->execute();
                
                // Retorna uma mensagem de sucesso (opcional)
                return ["success" => "Morador cadastrado com sucesso."];
            } catch (PDOException $e) {
                // Captura e retorna o erro
                return ["error" => $e->getMessage()];
            }
        }

        public function updateUserInfo($USU_DCEMAIL, $USU_DCNOME, $USU_DCBLOCO, $USU_DCAPARTAMENTO, $USU_DCNIVEL, $USU_DCSENHA, $USU_DCTELEFONE)
        {       
            // Verifica se a conexão já foi estabelecida
            if (!$this->pdo) {
                $this->conexao();
            }

            try {

                if($USU_DCSENHA != "IGNORE")
                {
                    $sql = "UPDATE USU_USUARIO 
                            SET
                            USU_DCEMAIL = :USU_DCEMAIL,
                            USU_DCNOME = :USU_DCNOME,
                            USU_DCBLOCO = :USU_DCBLOCO,
                            USU_DCNIVEL = :USU_DCNIVEL,
                            USU_DCSENHA = :USU_DCSENHA,
                            USU_DCTELEFONE = :USU_DCTELEFONE
                            WHERE USU_DCAPARTAMENTO = :USU_DCAPARTAMENTO";

                    $stmt = $this->pdo->prepare($sql);
                    $stmt->bindParam(':USU_DCSENHA', $USU_DCSENHA, PDO::PARAM_STR);
                }
                if($USU_DCSENHA == "IGNORE")
                {
                    $sql = "UPDATE USU_USUARIO 
                            SET
                            USU_DCEMAIL = :USU_DCEMAIL,
                            USU_DCNOME = :USU_DCNOME,
                            USU_DCBLOCO = :USU_DCBLOCO,
                            USU_DCAPARTAMENTO = :USU_DCAPARTAMENTO,
                            USU_DCNIVEL = :USU_DCNIVEL,
                            USU_DCTELEFONE = :USU_DCTELEFONE
                            WHERE USU_DCAPARTAMENTO = :USU_DCAPARTAMENTO";

                    $stmt = $this->pdo->prepare($sql);
                }                
            
                // Liga os parâmetros aos valores
                $stmt->bindParam(':USU_DCEMAIL', $USU_DCEMAIL, PDO::PARAM_STR);
                $stmt->bindParam(':USU_DCNOME', $USU_DCNOME, PDO::PARAM_STR);
                $stmt->bindParam(':USU_DCBLOCO', $USU_DCBLOCO, PDO::PARAM_STR);
                $stmt->bindParam(':USU_DCAPARTAMENTO', $USU_DCAPARTAMENTO, PDO::PARAM_STR);
                $stmt->bindParam(':USU_DCNIVEL', $USU_DCNIVEL, PDO::PARAM_STR);
                $stmt->bindParam(':USU_DCTELEFONE', $USU_DCTELEFONE, PDO::PARAM_STR);
            
                $stmt->execute();
           
                // Retorna uma mensagem de sucesso (opcional)
                return ["success" => "Morador atualizado com sucesso."];
            } catch (PDOException $e) {
                // Captura e retorna o erro
                return ["error" => $e->getMessage()];
            }
        }
        
        public function updateCheckboxEncomendasDisponivelMorador($ENC_IDENCOMENDA, $ENC_STENCOMENDA)
        {
            // Verifica se a conexão já foi estabelecida
            if (!$this->pdo) {
                $this->conexao();
            }

            try
            {         
                $sql = "UPDATE ENC_ENCOMENDA SET ENC_STENCOMENDA = :ENC_STENCOMENDA WHERE ENC_IDENCOMENDA = :ENC_IDENCOMENDA";
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':ENC_IDENCOMENDA', $ENC_IDENCOMENDA, PDO::PARAM_STR);
                $stmt->bindParam(':ENC_STENCOMENDA', $ENC_STENCOMENDA, PDO::PARAM_STR); 
                $stmt->execute();     

                //--------------------LOG----------------------//
                $LOG_DCTIPO = "ENCOMENDA";
                $LOG_DCMSG = "Encomenda com id $ENC_IDENCOMENDA foi alterada seu status para $ENC_STENCOMENDA";
                $LOG_DCUSUARIO = "PORTARIA";
                $LOG_DCCODIGO = $ENC_IDENCOMENDA;
                $LOG_DCAPARTAMENTO = "";
                $this->insertLogInfo($LOG_DCTIPO, $LOG_DCMSG, $LOG_DCUSUARIO, $LOG_DCAPARTAMENTO, $LOG_DCCODIGO);
                //--------------------LOG----------------------//

                return ["success" => "Encomenda atualizada com sucesso."];

            } catch (PDOException $e) {
                // Captura e retorna o erro
                return ["error" => $e->getMessage()];
            }
        }

        public function updateCheckboxEncomendasPortaria($ENC_IDENCOMENDA, $ENC_STENTREGA_MORADOR)
        {
            // Verifica se a conexão já foi estabelecida
            if (!$this->pdo) {
                $this->conexao();
            }

            if($ENC_STENTREGA_MORADOR == "ENTREGUE")
            {
                $now = new DateTime(); 
                $DATA = $now->format('Y-m-d H:i:s');
            }
            else
                {
                    $DATA = "0000-00-00 00:00:00";
                }


            try
            {         
                $sql = "UPDATE ENC_ENCOMENDA SET ENC_STENTREGA_MORADOR = :ENC_STENTREGA_MORADOR, ENC_DTENTREGA_MORADOR = :ENC_DTENTREGA_MORADOR WHERE ENC_IDENCOMENDA = :ENC_IDENCOMENDA";
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':ENC_IDENCOMENDA', $ENC_IDENCOMENDA, PDO::PARAM_STR);
                $stmt->bindParam(':ENC_STENTREGA_MORADOR', $ENC_STENTREGA_MORADOR, PDO::PARAM_STR); 
                $stmt->bindParam(':ENC_DTENTREGA_MORADOR', $DATA, PDO::PARAM_STR); 
                $stmt->execute();    
                
                                //--------------------LOG----------------------//
                                $LOG_DCTIPO = "ENCOMENDA";
                                $LOG_DCMSG = "Encomenda com id $ENC_IDENCOMENDA foi alterada seu status para $ENC_STENTREGA_MORADOR";
                                $LOG_DCUSUARIO = "PORTARIA";
                                $LOG_DCCODIGO = $ENC_IDENCOMENDA;
                                $LOG_DCAPARTAMENTO = "";
                                $this->insertLogInfo($LOG_DCTIPO, $LOG_DCMSG, $LOG_DCUSUARIO, $LOG_DCAPARTAMENTO, $LOG_DCCODIGO);
                                //--------------------LOG----------------------//

                return ["success" => "Encomenda atualizada com sucesso."];

            } catch (PDOException $e) {
                // Captura e retorna o erro
                return ["error" => $e->getMessage()];
            }
        }

        public function bdLogClear()
        {       
            // Verifica se a conexão já foi estabelecida
            if (!$this->pdo) {
                $this->conexao();
            }

            try 
            {
                $sql = "DELETE FROM LOG_LOGSISTEMA WHERE LOG_DTLOG < CURDATE() - INTERVAL 90 DAY";

                $stmt = $this->pdo->prepare($sql);           
                $stmt->execute();

                return "Limpeza dos logs realizada com sucesso.";
           
            } catch (PDOException $e) {
                // Captura e retorna o erro
                return "Erro ao executar a limpeza dos Logs: ".$e->getMessage();
            }
        }

        public function getLogInfo() 
        {          
                // Verifica se a conexão já foi estabelecida
                if(!$this->pdo){$this->conexao();}
            
            try{           
                $sql = "SELECT * FROM LOG_LOGSISTEMA ORDER BY LOG_DTLOG DESC";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                $this->ARRAY_LOGINFO = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                return ["error" => $e->getMessage()];
            }          
        }



    }
?>