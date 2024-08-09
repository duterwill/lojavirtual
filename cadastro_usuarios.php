<?php 
require_once('../../global/topo_inclusos.php');



$idpessoa = $_GET['idpessoa'];

if($idpessoa == ""){
$acao   = "Salvar";
}else{
$acao = "Alterar";  
}

//Recebe Acao
$acao_bd    = $_GET["acao"];
$acao_bd    .= $_POST["acao"];


//CAMPOS POST
$idpessoa .= $_POST['idpessoa'];
//$nome = $_POST['nome'];
//$sobrenome = $_POST['sobrenome'];

//$name = $nome . " " . $sobrenome;

$name                   = $_POST['nome'];
$cpf                    = $_POST['cpf'];
$document               = $_POST['document'];
$email                  = $_POST['email'];
$phone                  = $_POST['phone'];
$department             = $_POST['department'];
$iddivisao_departamento = $_POST['iddivisao_departamento'];
$empresa_alocada        = $_POST['empresa_alocada'];
$perfil_usuario         = $_POST['perfil_usuario'];
$perfil_credencial      = $_POST['perfil_credencial'];
$cargo                  = $_POST['cargo'];
$observacao             = $_POST['observacao'];
$status_login           = $_POST['status_login'];
$tipo_pessoa            = $_POST['tipo_pessoa'];
$idrota                 = $_POST['idrota'];
$perfil_operador        = $_POST['perfil_operador'];
$aprovador              = $_POST['aprovador'];

$nacionalidade          = $_POST['nacionalidade'];
$rne_passaporte         = $_POST['rne_passaporte'];


$idusuario_cadastro     = $_SESSION['usuarioID'];
$cadastrado_por         = $_SESSION['usuarioID'];
$datacadastro           = date('Y-m-d');
$data_hora_cadastro     = date('Y-m-d H:i:s');


//Dados da Tabela

$tabela ="Person";

$campos_tabela = "name, document, cpf, phone, email, department, idusuario_cadastro, iddivision, empresa_alocada, perfil_usuario, perfil_credencial, cargo, observacao, sistema_cliente, status_login, iddivisao_departamento, tipo_pessoa, idrota, data_hora_cadastro, perfil_operador, aprovador, nacionalidade, rne_passaporte";

$campos_value = "'$name', '$document', '$cpf', '$phone', '$email', '$department', '$idusuario_cadastro', '$IdDivision', '$empresa_alocada', '$perfil_usuario', '$perfil_credencial', '$cargo', '$observacao', 'Sim', '$status_login', '$iddivisao_departamento', '$tipo_pessoa', '$idrota', '$data_hora_cadastro', '$perfil_operador', '$aprovador', '$nacionalidade', '$rne_passaporte'";    
    
//INSERIR REGISTROS

if($acao_bd == "Salvar"){

if($nacionalidade == "brasileira"){

    if($cpf == ""){
        //CPF VAZIO
        echo "<script>";
        echo "alert('Cpf não informado, faça seu cadastro novamente!');";
        echo 'window.location.href = "cadastro_usuarios.php";';
        echo "</script>";

        exit();
    }

    //REGRA VALIDA CPF CASO POR ALGUM MOTIVO PASSE PELO AJAX
    $Cpf_atual = RemoverCaractereEspecial($cpf);  
    $verifica_cpf =  validaCPF($Cpf_atual);
    if($verifica_cpf){
        //TUDO OK COM CPF
    }else{

        echo "<script>";
        echo "alert('O Cpf informado esta inválido, por favor verifique e tente novamente.');";
        echo 'window.location.href = "cadastro_usuarios.php";';
        echo "</script>";
        exit();

    }


    //ANTES DE SALVAR VERIFICO SE NÃO ESTA DUPLICADO O CPF

    $SelecionaCPFDuplicado = mysql_query("SELECT idpessoa FROM $tabela WHERE cpf='$cpf' AND IdDivision='$IdDivision' AND excluido IS NULL", $conexao);
    $TotalCpfDuplicado = mysql_num_rows($SelecionaCPFDuplicado);

    if($TotalCpfDuplicado > 0){
        //ECHO CPF DUPLICADO GERA AVISO E RETORNA PARA O CADASTRO
        echo "<script>";
        echo "alert('Cpf Duplicado na Base faça seu cadastro novamente!');";
        echo 'window.location.href = "cadastro_usuarios.php";';
        echo "</script>";

        exit();
    }

    //FIM VERIFICA CPF DUPLICADO    

}

$salvar = Salvar($tabela, $campos_tabela, $campos_value);

// RETORNA ULTIMO ID INSERIDO

$id_insert = mysql_insert_id();

//INTELBRAS

$url_processamento = "https://p2pconecta.com.br/api/intelbras/fila_processamento_residentes.php?idpessoa=" . $id_insert;
$curl = curl_init();

curl_setopt_array($curl, array(
CURLOPT_URL => $url_processamento,
CURLOPT_RETURNTRANSFER => true,
CURLOPT_ENCODING => '',
CURLOPT_MAXREDIRS => 10,
CURLOPT_TIMEOUT => 0,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
CURLOPT_CUSTOMREQUEST => 'GET',
));

$response = curl_exec($curl);

curl_close($curl);

//FIM INTELBRAS

//GRAVO LOGS

$dados_novos = LogBd("idpessoa", $id_insert, $tabela);
$ipusuario = PegarIP();
$urlorigem = UrlAtual();
$dadosnavegador = DadosNavegador();
$categoria_log = "Cadastro_Novo_Usuario";
$acao_log = "Novo";
$datalog = date('Y-m-d H:i:s');

$SalvarLog = Salvar("Sistema_Logs", "idcondominio,url,ip,navegadorso,data_cadastro,cadastrado_por,categoria, dados_anterior, dados_novos, acao", "'$idcondominio','$urlorigem','$ipusuario','$dadosnavegador','$datalog','$cadastrado_por','$categoria_log', '$log_criado', '$dados_novos', '$acao_log'");

//FIM LOGS

$CodPersonBi = rand(1000000, 9999999);
    
$AlteroCodPessoa = Alterar("Person", "Id='$CodPersonBi'", "idpessoa", $id_insert);  

$datahora_solicitacao_processamento = date('Y-m-d H:i:s');

$SolicitarProcessamento = Alterar("Person", "datahora_solicitacao_processamento='$datahora_solicitacao_processamento', status_processamento='Solicitado', processamento_acao='Novo'", "idpessoa", $id_insert);


//CRIO NOTIFICAÇÃO 

    $mensagem = "*NOVO CADASTRO REALIZADO* \n";
    $mensagem .= "NOME:  $name \n";
    $mensagem .= "CPF:  $cpf \n";
    $mensagem .= "CELULAR:  $phone \n";
    $mensagem .= "E-MAIL:  $email \n";
    $mensagem .= "EMPRESA: $NomeEmpresa\n";    
    $mensagem .= "ROTA: ". RotaCliente($idrota) . "\n";    
    $mensagem .= "PERFIL: $perfil_usuario \n";    
    $mensagem .= "CARGO: $cargo \n";    
    $mensagem .= "DATA SOLICITADO: " . date('d/m/Y H:i:s'). " \n";        
    $mensagem .= "CRIADO POR: " . $NomeUsuario . " \n";        
    
            
    //$EnviarWhatsApp = EnviarWhatsApp('5511940448420', $mensagem, $logo_empresa_atual);

//FIM PROCESSAMENTO

     
$redireciona = VerificaSql($salvar, "cadastro_usuarios.php?idpessoa=".$id_insert."", "listar_usuarios.php");



}


// CAMPOS ALTERAR 


if($acao_bd == "Alterar"){

if($nacionalidade == "brasileira"){

    //REGRA VALIDA CPF CASO POR ALGUM MOTIVO PASSE PELO AJAX
    $Cpf_atual = RemoverCaractereEspecial($cpf);  
    $verifica_cpf =  validaCPF($Cpf_atual);
    if($verifica_cpf){
        //TUDO OK COM CPF
    }else{

        echo "<script>";
        echo "alert('O Cpf informado esta inválido, por favor verifique e tente novamente.');";
        echo 'window.location.href = "cadastro_usuarios.php?idpessoa='.$idpessoa.'";';
        echo "</script>";
        exit();

    }     


    //ANTES DE SALVAR VERIFICO SE NÃO ESTA DUPLICADO O CPF

    $SelecionaCPFDuplicado = mysql_query("SELECT idpessoa FROM $tabela WHERE cpf='$cpf' AND IdDivision='$IdDivision' AND idpessoa != '$idpessoa' AND excluido IS NULL", $conexao);
    $TotalCpfDuplicado = mysql_num_rows($SelecionaCPFDuplicado);

    if($TotalCpfDuplicado > 0){
        //ECHO CPF DUPLICADO GERA AVISO E RETORNA PARA O CADASTRO
        echo "<script>";
        echo "alert('Cpf Duplicado na Base faça seu cadastro novamente!');";
        echo 'window.location.href = "cadastro_usuarios.php?idpessoa='.$idpessoa.'";';
        echo "</script>";

        exit();
    }  

} 

//VERIFICO REGRA DE PROCESSAMENTO

    $SelecionaPessoa = Seleciona("Person", "WHERE idpessoa='$idpessoa'", "LIMIT 0,1");
    while($pessoa = mysql_fetch_array($SelecionaPessoa)){

        $nome_base              = $pessoa['name'];
        $cpf_base               = $pessoa['cpf'];
        $nacionalidade_base     = $pessoa['nacionalidade'];
        $rne_passaporte_base    = $pessoa['rne_passaporte'];
        $status_base            = $pessoa['status_login'];
        $rota_base              = $pessoa['idrota'];
        $idsituator_base        = $pessoa['idsituator'];
        $codigow_base           = $pessoa['crendecial_codigow'];
    }

    if($nome_base != $name){
        $Processar = "Sim";
    }

    if($cpf_base != $cpf){
        $Processar = "Sim";
    }

    if($status_base != $status_login){
        $Processar = "Sim";
    }

    if($rota_base != $idrota){
        $Processar = "Sim";
    }

    if($nacionalidade_base != $nacionalidade){
        $Processar = "Sim";
    }    

    if($rne_passaporte_base != $nacionalidade){
        $Processar = "Sim";
    }

    
    $campos_alterar = "name='$name', document='$document', cpf='$cpf', phone='$phone', email='$email', department='$department', empresa_alocada='$empresa_alocada', perfil_usuario='$perfil_usuario', perfil_credencial='$perfil_credencial', cargo='$cargo', observacao='$observacao', sistema_cliente='Sim', status_login='$status_login', iddivisao_departamento='$iddivisao_departamento', tipo_pessoa='$tipo_pessoa', idrota='$idrota', perfil_operador='$perfil_operador', aprovador='$aprovador', nacionalidade='$nacionalidade', rne_passaporte='$rne_passaporte'";


    if($Processar == "Sim"){
           


        $datahora_solicitacao_processamento = date('Y-m-d H:i:s');

        $SolicitarProcessamento = Alterar("Person", "datahora_solicitacao_processamento='$datahora_solicitacao_processamento', status_processamento='Solicitado', processamento_acao='Alterar'", "idpessoa", $idpessoa);


        //CRIO NOTIFICAÇÃO 

            $mensagem = "*ALTERAÇÃO DE PESSOA* \n";
            $mensagem .= "STATUS:  $status_login \n";            
            $mensagem .= "IDSITUATOR:  $idsituator_base \n";            
            $mensagem .= "COD. W:  $codigow_base \n";
            $mensagem .= "NOME:  $name \n";
            $mensagem .= "NACIONALIDADE:  $nacionalidade \n";
            $mensagem .= "CPF:  $cpf \n";
            $mensagem .= "CELULAR:  $phone \n";
            $mensagem .= "E-MAIL:  $email \n";
            $mensagem .= "EMPRESA: $NomeEmpresa\n";    
            $mensagem .= "ROTA: ". RotaCliente($idrota) . "\n";    
            $mensagem .= "PERFIL: $perfil_usuario \n";    
            $mensagem .= "CARGO: $cargo \n";    
            $mensagem .= "DATA SOLICITADO: " . date('d/m/Y H:i:s'). " \n";        
            $mensagem .= "CRIADO POR: " . $NomeUsuario . " \n";        
            
                    
            //$EnviarWhatsApp = EnviarWhatsApp('5511940448420', $mensagem, $logo_empresa_atual);

        //FIM PROCESSAMENTO           

    }

//PEGOS DADOS ATUAIS ANTES DE ALTERAR (REGRA LOGS)
$log_antes = LogBd("idpessoa", $idpessoa, $tabela);

$atualizar = Alterar($tabela, $campos_alterar, "idpessoa", $idpessoa);



//INTELBRAS

$url_processamento = "https://p2pconecta.com.br/api/intelbras/fila_processamento_residentes.php?idpessoa=" . $idpessoa;
$curl = curl_init();

curl_setopt_array($curl, array(
CURLOPT_URL => $url_processamento,
CURLOPT_RETURNTRANSFER => true,
CURLOPT_ENCODING => '',
CURLOPT_MAXREDIRS => 10,
CURLOPT_TIMEOUT => 0,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
CURLOPT_CUSTOMREQUEST => 'GET',
));

$response = curl_exec($curl);

curl_close($curl);

//FIM INTELBRAS


$log_novo = LogBd("idpessoa", $idpessoa, $tabela);
$acao_log = "AlterarUsuario";  
$categoria_log = "Alterar_Usuario"; 
$ipusuario = PegarIP();
$urlorigem = UrlAtual();
$dadosnavegador = DadosNavegador();    
$datalog = date('Y-m-d H:i:s');

$SalvarLog = Salvar("Sistema_Logs", "idcondominio,url,ip,navegadorso,data_cadastro,cadastrado_por,categoria, dados_anterior, dados_novos, acao", "'$idcondominio','$urlorigem','$ipusuario','$dadosnavegador','$datalog','$cadastrado_por','$categoria_log', '$log_antes', '$log_novo', '$acao_log'");
    
//REGRA PHILLIPE 09/05/2023 09:58
//REMOVER CREDENCIAL SE INATIVAR PESSOA
if($status_login == "Inativo"){

//$AlterarPessoa = Alterar("Person", "crendecial_codigow='', crendecial_rfid=''", "idpessoa", $idpessoa);

}    
  
    
    
$redireciona = VerificaSql($atualizar, "cadastro_usuarios.php?idpessoa=".$idpessoa."", "cadastro_usuarios.php?idpessoa=".$idpessoa."");

}



//CAMPOS SELECT 

if($acao == "Alterar"){

$selecionar_pessoa = Seleciona($tabela, "WHERE idpessoa=$idpessoa", "") or die(mysql_error());
while($linha_pessoa=mysql_fetch_array($selecionar_pessoa)){
    
$idpessoa                       = $linha_pessoa['idpessoa']; 
$id                             = $linha_pessoa['id']; 
$idsituator                     = $linha_pessoa['idsituator']; 
$iddivision                     = $linha_pessoa['iddivision']; 
$name                           = $linha_pessoa['name'];
    
$nome_completo = explode(" ",$name);    
$nome = $nome_completo[0];
$sobrenome = $nome_completo[1] . " " . $nome_completo[2] . " " . $nome_completo[3]. " " . $nome_completo[4];    
    
$document                       = $linha_pessoa['document'];
$cpf                            = $linha_pessoa['cpf'];
$phone                          = $linha_pessoa['phone'];
$phone2                         = $linha_pessoa['phone2'];
$birthday                       = $linha_pessoa['birthday'];
$email                          = $linha_pessoa['email'];
$personprofileid                = $linha_pessoa['personprofileid'];
$department                     = $linha_pessoa['department'];
$company                        = $linha_pessoa['company']; 
$trocarsenha                    = $linha_pessoa['trocarsenha'];    
$master                         = $linha_pessoa['master'];
$status_login                   = $linha_pessoa['status_login'];
$cliente                        = $linha_pessoa['cliente'];
$tipo_pessoa                    = $linha_pessoa['tipo_pessoa'];
$idrota                         = $linha_pessoa['idrota'];
$perfil_operador                = $linha_pessoa['perfil_operador'];
$aprovador                      = $linha_pessoa['aprovador'];
$nacionalidade                  = $linha_pessoa['nacionalidade'];
$rne_passaporte                 = $linha_pessoa['rne_passaporte'];

    
   
$cargo                          = $linha_pessoa['cargo'];    
$empresa_alocada                = $linha_pessoa['empresa_alocada'];    
$perfil_credencial              = $linha_pessoa['perfil_credencial'];    
$perfil_usuario                 = $linha_pessoa['perfil_usuario'];    
$observacao                     = $linha_pessoa['observacao'];  
$iddivisao_departamento         = $linha_pessoa['iddivisao_departamento'];       
   
$credencial_domingo             = $linha_pessoa['credencial_domingo'];    
$credencial_segunda             = $linha_pessoa['credencial_segunda'];    
$credencial_terca               = $linha_pessoa['credencial_terca'];    
$credencial_quarta              = $linha_pessoa['credencial_quarta'];    
$credencial_quinta              = $linha_pessoa['credencial_quinta'];    
$credencial_sexta               = $linha_pessoa['credencial_sexta'];    
$credencial_sabado              = $linha_pessoa['credencial_sabado'];  
    
$credencia_liberado             = $linha_pessoa['credencia_liberado'];
$credencial_tipo                = $linha_pessoa['credencial_tipo'];
$crendecial_codigow             = $linha_pessoa['crendecial_codigow'];
$crendecial_rfid                = $linha_pessoa['rfid'];
$crendencial_datainicio         = $linha_pessoa['crendencial_datainicio'];
$crendencial_datafim            = $linha_pessoa['crendencial_datafim'];
$credencial_domingo_horainicio  = $linha_pessoa['credencial_domingo_horainicio'];
$credencial_domingo_horafim     = $linha_pessoa['credencial_domingo_horafim'];
$credencial_segunda_horainicio  = $linha_pessoa['credencial_segunda_horainicio'];
$credencial_segunda_horafim     = $linha_pessoa['credencial_segunda_horafim'];
$credencial_terca_horainicio    = $linha_pessoa['credencial_terca_horainicio'];
$credencial_terca_horafim       = $linha_pessoa['credencial_terca_horafim'];
$credencial_quarta_horainicio   = $linha_pessoa['credencial_quarta_horainicio'];
$credencial_quarta_horafim      = $linha_pessoa['credencial_quarta_horafim'];
$credencial_quinta_horainicio   = $linha_pessoa['credencial_quinta_horainicio'];
$credencial_quinta_horafim      = $linha_pessoa['credencial_quinta_horafim'];
$credencial_sexta_horainicio    = $linha_pessoa['credencial_sexta_horainicio'];
$credencial_sexta_horafim       = $linha_pessoa['credencial_sexta_horafim'];
$credencial_sabado_horainicio   = $linha_pessoa['credencial_sabado_horainicio'];
$credencial_sabado_horafim      = $linha_pessoa['credencial_sabado_horafim'];
   
$config_domingo                 = $linha_pessoa['config_domingo'];    
$config_segunda                 = $linha_pessoa['config_segunda'];    
$config_terca                   = $linha_pessoa['config_terca'];    
$config_quarta                  = $linha_pessoa['config_quarta'];    
$config_quinta                  = $linha_pessoa['config_quinta'];    
$config_sexta                   = $linha_pessoa['config_sexta'];    
$config_sabado                  = $linha_pessoa['config_sabado'];
    
$sistema_cliente                = $linha_pessoa['sistema_cliente'];
$sistema_ima                    = $linha_pessoa['sistema_ima'];
$sistema_bi                     = $linha_pessoa['sistema_bi'];
$sistema_operacao               = $linha_pessoa['sistema_operacao'];
$sistema_condominio               = $linha_pessoa['sistema_condominio'];
    
$config_duplaverificacao        = $linha_pessoa['config_duplaverificacao'];    
$config_liberadoacesso          = $linha_pessoa['config_liberadoacesso'];   
$config_datainicio              = $linha_pessoa['config_datainicio'];    
$config_datafim                 = $linha_pessoa['config_datafim'];    
$config_domingo_horainicio      = $linha_pessoa['config_domingo_horainicio'];    
$config_domingo_horafim         = $linha_pessoa['config_domingo_horafim'];    
$config_segunda_horainicio      = $linha_pessoa['config_segunda_horainicio'];    
$config_segunda_horafim         = $linha_pessoa['config_segunda_horafim'];    
$config_terca_horainicio        = $linha_pessoa['config_terca_horainicio'];    
$config_terca_horafim           = $linha_pessoa['config_terca_horafim'];    
$config_quarta_horainicio       = $linha_pessoa['config_quarta_horainicio'];    
$config_quarta_horafim          = $linha_pessoa['config_quarta_horafim'];    
$config_quinta_horainicio       = $linha_pessoa['config_quinta_horainicio'];    
$config_quinta_horafim          = $linha_pessoa['config_quinta_horafim'];    
$config_sexta_horainicio        = $linha_pessoa['config_sexta_horainicio'];    
$config_sexta_horafim           = $linha_pessoa['config_sexta_horafim'];    
$config_sabado_horainicio       = $linha_pessoa['config_sabado_horainicio'];    
$config_sabado_horafim          = $linha_pessoa['config_sabado_horafim'];    
    
    
$convites_domingo_horainicio    = $linha_pessoa['convites_domingo_horainicio'];    
$convites_domingo_horafim       = $linha_pessoa['convites_domingo_horafim'];    
$convites_segunda_horainicio    = $linha_pessoa['convites_segunda_horainicio'];    
$convites_segunda_horafim       = $linha_pessoa['convites_segunda_horafim'];    
$convites_terca_horainicio      = $linha_pessoa['convites_terca_horainicio'];    
$convites_terca_horafim         = $linha_pessoa['convites_terca_horafim'];    
$convites_quarta_horainicio     = $linha_pessoa['convites_quarta_horainicio'];    
$convites_quarta_horainicio     = $linha_pessoa['convites_quarta_horainicio'];    
$convites_quarta_horafim        = $linha_pessoa['convites_quarta_horafim'];    
$convites_quinta_horainicio     = $linha_pessoa['convites_quinta_horainicio'];    
$convites_quinta_horafim        = $linha_pessoa['convites_quinta_horafim'];    
$convites_sexta_horainicio      = $linha_pessoa['convites_sexta_horainicio'];    
$convites_sexta_horafim         = $linha_pessoa['convites_sexta_horafim'];    
$convites_sexta_horainicio      = $linha_pessoa['convites_sexta_horainicio'];    
$convites_sabado_horainicio     = $linha_pessoa['convites_sabado_horainicio'];    
$convites_sabado_horafim        = $linha_pessoa['convites_sabado_horafim'];    

    
$convites_domingo               = $linha_pessoa['convites_domingo'];    
$convites_segunda               = $linha_pessoa['convites_segunda'];    
$convites_terca                 = $linha_pessoa['convites_terca'];    
$convites_quarta                = $linha_pessoa['convites_quarta'];    
$convites_quinta                = $linha_pessoa['convites_quinta'];    
$convites_sexta                 = $linha_pessoa['convites_sexta'];    
$convites_sabado                = $linha_pessoa['convites_sabado'];  
    
$foto_pessoa                    = $linha_pessoa['foto_pessoa'];   
    
 
//RESTAURANTE

$qtde_dia_restaurante           = $linha_pessoa['qtde_dia_restaurante'];       
$qtde_mes_restaurante           = $linha_pessoa['qtde_mes_restaurante'];
$liberado_restaurante           = $linha_pessoa['liberado_restaurante'];
      

$idrestaurante_grupo            = $linha_pessoa['idrestaurante_grupo'];   
$qtde_dia_restaurante_grupo     = $linha_pessoa['qtde_dia_restaurante_grupo'];       
$qtde_mes_restaurante_grupo     = $linha_pessoa['qtde_mes_restaurante_grupo'];       
$idescala                       = $linha_pessoa['idescala'];       

$cartao_1356_a                  = $linha_pessoa['cartao_1356_a'];   
$cartao_1356_s                  = $linha_pessoa['cartao_1356_s'];   
$cartao_1356_w                  = $linha_pessoa['cartao_1356_w'];  

$gerar_convite_restaurante      = $linha_pessoa['gerar_convite_restaurante'];   

    
} 
} 

if($gerar_convite_restaurante == ""){
    $gerar_convite_restaurante = 2;
}


//REGRA STATUS E PERFIL 
//17/06/2022 - 11:57 - SOLICITADO PHILLIPE

if($status_login == ""){
    $status_login = "Ativo";
}

if($perfil_usuario == ""){
    $perfil_usuario = "FUNCIONARIO";
}


if($_GET["ExcluirFoto"] == "Sim"){

    $AlterarPessoaFoto = Alterar("Person", "foto_pessoa=null, foto_base64=null", "idpessoa", $idpessoa);

    //INTELBRAS

    $url_processamento = "https://p2pconecta.com.br/api/intelbras/fila_processamento_residentes.php?idpessoa=" . $idpessoa ."&acao_remover=Sim";
    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => $url_processamento,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    //FIM INTELBRAS


    $redireciona = VerificaSql($atualizar, "cadastro_usuarios.php?idpessoa=".$idpessoa."", "cadastro_usuarios.php?idpessoa=".$idpessoa."");


}

//EXCLUIR REGISTRO 



if($_GET["ExcluirPessoa"] == "Sim"){


    //REGRA DE PROCESSAMENTO
    //15/06/2022
    /*
    datahora_solicitacao_processamento
    status_processamento
    datahora_concluido_processamento
    crendecial_codigow_anterior
    */

    $datahora_solicitacao_processamento = date('Y-m-d H:i:s');

    $SolicitarProcessamento = Alterar("Person", "datahora_solicitacao_processamento='$datahora_solicitacao_processamento', status_processamento='Solicitado', processamento_acao='Excluir'", "idpessoa", $idpessoa);


    //CRIO NOTIFICAÇÃO 

    $mensagem = "*EXCLUSÃO DE PESSOA* \n";
    $mensagem .= "IDSITUATOR:  $idsituator \n";
    $mensagem .= "COD. W:  $crendecial_codigow \n";
    $mensagem .= "NOME:  $name \n";
    $mensagem .= "CPF:  $cpf \n";
    $mensagem .= "CELULAR:  $phone \n";
    $mensagem .= "E-MAIL:  $email \n";
    $mensagem .= "EMPRESA: $NomeEmpresa\n";    
    $mensagem .= "ROTA: ". RotaCliente($idrota) . "\n";    
    $mensagem .= "PERFIL: $perfil_usuario \n";    
    $mensagem .= "CARGO: $cargo \n";    
    $mensagem .= "DATA SOLICITADO: " . date('d/m/Y H:i:s'). " \n";        
    $mensagem .= "CRIADO POR: " . $NomeUsuario . " \n";        

        
    //$EnviarWhatsApp = EnviarWhatsApp('5511940448420', $mensagem, $logo_empresa_atual);

    //FIM PROCESSAMENTO 


//LOG ATUAL ANTES DE EXCLUIR
$log_criado = LogBd("idpessoa", $idpessoa, $tabela);

$deletar = Alterar($tabela, "excluido='Sim'", "idpessoa", $idpessoa);
$deletar = Alterar("Person_Veiculos", "excluido='Sim'", "idpessoa", $idpessoa);

//INTELBRAS

$url_processamento = "https://p2pconecta.com.br/api/intelbras/fila_processamento_residentes.php?idpessoa=" . $idpessoa;
$curl = curl_init();

curl_setopt_array($curl, array(
CURLOPT_URL => $url_processamento,
CURLOPT_RETURNTRANSFER => true,
CURLOPT_ENCODING => '',
CURLOPT_MAXREDIRS => 10,
CURLOPT_TIMEOUT => 0,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
CURLOPT_CUSTOMREQUEST => 'GET',
));

$response = curl_exec($curl);

curl_close($curl);

//FIM INTELBRAS


//GRAVO LOGS

$ipusuario = PegarIP();
$urlorigem = UrlAtual();
$dadosnavegador = DadosNavegador();
$categoria_log = "Excluir_Usuario";
$acao_log = "Exclusao";
$datalog = date('Y-m-d H:i:s');

$SalvarLog = Salvar("Sistema_Logs", "idcondominio,url,ip,navegadorso,data_cadastro,cadastrado_por,categoria, dados_anterior, dados_novos, acao", "'$idcondominio','$urlorigem','$ipusuario','$dadosnavegador','$datalog','$cadastrado_por','$categoria_log', '$log_criado', '$dados_novos', '$acao_log'");

//FIM LOGS
    
$redireciona = VerificaSql($deletar, "listar_usuarios.php", "cadastro_usuarios.php?idpessoa=".$idpessoa."");

} 

/*
//VERIFICA SE USUARIO PERTENCE A EMPRESA DO USUARIO LOGADA SE NÃO DIRECIONA PARA HOME

if($iddivision > 0 and $iddivision != $IdDivision){
    echo "ESTA ACESSANDO DO CLIENTE ERRADO"
}
*/




?>
<!DOCTYPE html>
<html lang="pt-Br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= $TituloSitema;?> Cadastro de Usuário</title>

    <!-- HEADPADRAO -->
    <?php require_once($DiretorioRaizGlobal.'head_padrao.php');?>
    <!-- FIM HEADPADRAO -->

    <link rel="stylesheet" href="<?= $DiretorioVirtual;?>assets/vendors/dropify/dist/dropify.min.css">
    <link rel="stylesheet" href="<?= $DiretorioVirtual;?>assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="<?= $DiretorioVirtual;?>assets/vendors/select2/select2.min.css">

    <style>
        .dropify-wrapper {
            height: 250px !important;
        }

        .select2-selection {
            height: 36px !important;
        }
    </style>

</head>

<body class="sidebar-dark">
    <div class="main-wrapper">

        <!-- LATERAL -->
        <?php require_once($DiretorioRaizGlobal.'lateral.php');?>
        <!-- FIM LATERAL -->


        <div class="page-wrapper">


            <!-- CABEÇALHO -->
            <?php require_once($DiretorioRaizGlobal.'cabecalho.php');?>
            <!-- FIM CABEÇALHO -->


            <div class="page-content">

                <nav class="page-breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Configurações</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Cadastro de Usuário</li>
                    </ol>
                </nav>
                <div class="row">
                    <div class="col-md-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">  
                            <?php if($idpessoa > 0){ ?>
                                    <span style="font-size: medium;font-weight: 900;">DADOS DA PESSOA:  <?= $nome . " " . $sobrenome;?></span>
                            <?php }else{  ?>
                                    <span style="font-size: medium;font-weight: 900;">CADASTRAR NOVA PESSOA</span>
                                <?php }?>
                                    <a href="<?= $DiretorioVirtual_Configuracao;?>cadastro_usuarios.php">
                                        <button type="button" class="btn btn-success" style="float: right;"><i data-feather="user-plus"></i> NOVO USUÁRIO</button>
                                    </a>
                               
                            </div>
                        </div>
                    </div>
                    

                </div>
                <form method="post" action="cadastro_usuarios.php" enctype="multipart/form-data">
                    <div class="row">

                        <div class="col-md-3 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">

                                    <?php if($idpessoa > 0 and $_GET['Aba'] == ""){ 

                                        $SelecionaUltimoLog = Seleciona("Fila_Processamento_Equipamentos",  "WHERE action='Facial' AND UserID='$idpessoa'", "ORDER BY idfila_processamento DESC LIMIT 0,1");
                                        $TotalUltimoLog = mysql_num_rows($SelecionaUltimoLog);

                                        if($TotalUltimoLog > 0){
                                            while($log = mysql_fetch_array($SelecionaUltimoLog)){

                                                //echo trim($log['retorno_equipamento']) . "TO AQUI";

                                                if(trim($log['retorno_equipamento']) == "OK"){
                                                    $data_enviado = date('d/m/Y H:i:s', strtotime($log['data_envio_equipamento']));
                                                    $sincronizado_equipamento_facial = "Sim";
                                                    //$texto_sincronizado = "<strong>Sincronismo Facial</strong><br>Efetuado com Sucesso! <br> Data: " . $data_enviado;
                                                    $texto_sincronizado = "<strong>Sincronismo Facial</strong><br>Efetuado com Sucesso!";
                                                    $icon = "success";
                                                }else{
                                                    $sincronizado_equipamento_facial = "Nao";
                                                    
                                                    $texto_sincronizado = "<strong>Sincronismo Facial</strong><br>Erro ao efetuar o Sincronismo Facial com o Equipamento, por favor verificar imagem e tentar novamente!";
                                                    $icon = "danger";
                                                }

                                            }
                                        }
                                    ?>

                                    <?php

                                    $extensao = explode(".", $foto_pessoa);
                                    $extensao_foto = $extensao[1];
                                    ?>

                                    <?php if($sincronizado_equipamento_facial != "" and $TotalUltimoLog > 0 and $foto_pessoa != ""){ ?>

                                    <div class="alert alert-icon-<?= $icon;?>" role="alert">
                                    <i data-feather="alert-circle"></i>
                                    <?= $texto_sincronizado;?>
                                    </div>

                                    <?php } ?>

                                    <?php }?> 

                                    <h6 class="card-title">Imagem 
                                        <?php if($idpessoa > 0){ ?>
                                    <button type="button" class="btn btn-dark btn-icon" data-toggle="tooltip" data-placement="top" title="Adicionar Foto no Usuário" style="background: aliceblue; padding: 7px; float: right;" OnClick="FotoWebCam('<?= $idpessoa;?>', '<?= utf8_encode($name);?>')"><img src="<?= $DiretorioVirtual;?>img/icone_webcam.png" style="width: 20px;height: 20px;"></button>
                                <?php }?>
                                    </h6> 
                                    <form class="forms-sample">
                                        <div class="form-group">
                                            
                                            <?php    

                                            $extensao = explode(".", $foto_pessoa);
                                            $extensao_foto = $extensao[1];

                                            if($extensao_foto){
                                                
                                            if($foto_pessoa != ""){
                                                $FotoExibe = "<img src='" . $DiretorioVirtual_Upload_Pessoas_UsuariosCliente . $foto_pessoa . "' style='max-width: 500px; max-height: 500px; width: 100%; border: 1px solid #ccc; padding: 5px;'>";
                                                echo $FotoExibe;
                                            }

                                        }
                                        ?>

                                        <?php                                                         
                                        if($idpessoa > 0 and $PerfilUsuario == "ADMINISTRADOR" and $foto_pessoa != ""){
                                        ?>
                                        <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#ExcluirFoto" style="float: right; margin: 10px;"><i data-feather="trash"></i> REMOVER FOTO</button>

                                        <?php }?>

                                            <div class="progress">
                                            <div id="progresso" class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="0"
                                            aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>
                                            </div>
                                            <input type="file" id="myDropify" class="border" accept="image/*" name="image">
                                        </div>

                                        

                                        <!--<button type="button" class="btn btn-success" style="width: 100%; display: none;"><i data-feather="camera"></i> TIRAR FOTO</button>-->
                                    </form>

                                    

                                </div>
                            </div>
                        </div>

                        <div class="col-md-9 stretch-card">



                            <div class="card">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <a href="cadastro_usuarios.php?idpessoa=<?= $idpessoa;?>">
                                            <button type="button" class="btn btn-<?php if($_GET['Aba'] == ""){ echo "success"; }else{ echo "secondary";}?>" data-toggle="tooltip" data-placement="top" title="Dados do Usuário"><i data-feather="user"></i> PESSOA</button>
                                        </a>
                                        <?php 
                                        if($idpessoa > 0 and $status_login == "Ativo"){
                                    ?>
                                        <a href="cadastro_usuarios.php?idpessoa=<?= $idpessoa;?>&Aba=Veiculos">
                                            <button type="button" class="btn btn-<?php if($_GET['Aba'] == "Veiculos"){ echo "success"; }else{ echo "secondary";}?>"><i data-feather="truck"></i> VEÍCULOS</button>
                                        </a>
                                        <a href="cadastro_usuarios.php?idpessoa=<?= $idpessoa;?>&Aba=Credenciais">
                                            <button type="button" class="btn btn-<?php if($_GET['Aba'] == "Credenciais"){ echo "success"; }else{ echo "secondary";}?>"><i data-feather="lock"></i> CREDENCIAIS</button>
                                        </a>
                                        <a href="cadastro_usuarios.php?idpessoa=<?= $idpessoa;?>&Aba=Acesso">
                                            <button type="button" class="btn btn-<?php if($_GET['Aba'] == "Acesso"){ echo "success"; }else{ echo "secondary";}?>"><i data-feather="key"></i> CONFIG. USUÁRIO</button>
                                        </a>

                                        <a href="cadastro_usuarios.php?idpessoa=<?= $idpessoa;?>&Aba=Sistema" style="display: none;">
                                            <button type="button" class="btn btn-<?php if($_GET['Aba'] == "Sistema"){ echo "success"; }else{ echo "secondary";}?>"><i data-feather="cpu"></i> PERFIL USUÁRIO</button>
                                        </a>

                                        <a href="cadastro_usuarios.php?idpessoa=<?= $idpessoa;?>&Aba=Convites">
                                            <button type="button" class="btn btn-<?php if($_GET['Aba'] == "Convites"){ echo "success"; }else{ echo "secondary";}?>"><i data-feather="calendar"></i> CONVITES</button>
                                        </a>
                                        <?php if($ProgramaRestaurante == "Sim"){ ?>
                                        <a href="cadastro_usuarios.php?idpessoa=<?= $idpessoa;?>&Aba=Restaurante">
                                            <button type="button" class="btn btn-<?php if($_GET['Aba'] == "Restaurante"){ echo "success"; }else{ echo "secondary";}?>"><i data-feather="coffee"></i> RESTAURANTE</button>
                                        </a>
                                        <a href="cadastro_usuarios.php?idpessoa=<?= $idpessoa;?>&Aba=EscalaRestaurante">
                                            <button type="button" class="btn btn-<?php if($_GET['Aba'] == "EscalaRestaurante"){ echo "success"; }else{ echo "secondary";}?>"><i data-feather="sliders"></i> ESCALA RESTAURANTE</button>
                                        </a>
                                        <?php }?>

                                        <?php if($IdDivision == 27){ ?>
                                        <a href="cadastro_usuarios.php?idpessoa=<?= $idpessoa;?>&Aba=LogsAlteracoes">
                                            <button type="button" class="btn btn-<?php if($_GET['Aba'] == "LogsAlteracoes"){ echo "success"; }else{ echo "secondary";}?>"><i data-feather="calendar"></i> LOGS ALTERAÇÕES</button>
                                        </a>

                                        <?php }?>


                                        
                                        
                                        <?php }?>
                                    </div>
                                </div>
                                <?php if($_GET['Aba'] == ""){ ?>
                                <!-- DADOS PESSOAS -->

                                <input type="hidden" name="idpessoa" value="<?= $idpessoa;?>">
                                <input type="hidden" name="acao" value="<?= $acao;?>">

                                <div class="card-body" id="pessoas">


                                    <h6 class="card-title">Dados do Usuário</h6>


                                    <div class="row">

                                        <div class="col-sm-3">
                                            <div class="form-group">
                                                <label class="control-label">Nacionalidade</label>
                                                <select class="form-control" name="nacionalidade" id="nacionalidade">
                                                    <option value="brasileira" <?php if($nacionalidade == "brasileira"){ echo "SELECTED";}?>>Brasileira</option>
                                                    <option value="estrangeira" <?php if($nacionalidade == "estrangeira"){ echo "SELECTED";}?>>Estrangeira</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-3 div-sel" id="brasileira">
                                            <div class="form-group">
                                                <label class="control-label">CPF</label>
                                                <?php
                                                //VERIFICO SE JA EXISTE CADASTRO PARA TRAVAR OU NÃO CPF
                                                $require_cpf = "required";
                                                if($idconvite > 0 and $celular != ""){
                                                    $require_cpf = "";
                                                }
                                                ?>
                                                <input type="text" class="form-control" id="cpf" data-inputmask-alias="999.999.999-99" name="cpf" value="<?= $cpf;?>" autocomplete="off" onchange="CpfBloqueado()" required>
                                            </div>
                                        </div><!-- Col -->


                                        <div class="col-sm-3 div-sel" id="estrangeira">
                                            <div class="form-group">
                                                <label class="control-label">PASSAPORTE OU RNE</label>
                                                <?php
                                                //VERIFICO SE JA EXISTE CADASTRO PARA TRAVAR OU NÃO CPF
                                                $require_cpf = "required";
                                                if($idconvite > 0 and $celular != ""){
                                                    $require_cpf = "";
                                                }
                                                ?>
                                                <input type="text" class="form-control" id="rne_passaporte" name="rne_passaporte" value="<?= $rne_passaporte;?>" autocomplete="off">
                                            </div>
                                        </div><!-- Col -->

                                        <!--
                                        <div class="col-sm-3">
                                            <div class="form-group">
                                                <label class="control-label">CPF*</label>
                                                <input type="text" class="form-control" data-inputmask-alias="999.999.999-99" value="<?= $cpf;?>" id="cpf" name="cpf" required onchange="CpfBloqueado()" autocomplete="off">
                                            </div>
                                        </div>
                                        -->
                                        <div class="col-sm-3">
                                            <div class="form-group">
                                                <label class="control-label">CELULAR</label>
                                                <input type="text" class="form-control" value="<?= $phone;?>" name="phone" data-inputmask-alias="(99) 99999-9999">
                                            </div>
                                        </div><!-- Col -->

                                        <div class="col-sm-3">
                                            <div class="form-group">
                                                <label class="control-label">E-MAIL*</label>
                                                <input type="text" name="email" class="form-control" value="<?= $email;?>" style="text-transform: none !important;">
                                            </div>
                                        </div><!-- Col -->
                                        <div class="col-sm-8">
                                            <div class="form-group">
                                                <label class="control-label">NOME COMPLETO*</label>
                                                <input type="text" class="form-control" value="<?= $name;?>" name="nome" required>
                                            </div>
                                        </div><!-- Col -->

                                        <div class="col-sm-4" style="display: none;">
                                            <div class="form-group">
                                                <label class="control-label">SOBRENOME*</label>
                                                <input type="text" class="form-control" name="sobrenome" value="<?= $sobrenome;?>">
                                            </div>
                                        </div><!-- Col -->

                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                <label class="control-label">EMPRESA ALOCADA</label>
                                                <input type="text" class="form-control" name="empresa_alocada" value="<?= $empresa_alocada;?>">
                                            </div>
                                        </div><!-- Col -->

                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                <label class="control-label">EMPRESA</label>
                                                <input type="text" class="form-control" value="<?= $NomeEmpresa;?>" disabled>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                <label class="control-label">PERFIL DO USUÁRIO</label>
                                                <select class="form-control" name="perfil_usuario">

                                                    <?if($perfil_usuario != ""){ ?>
                                                    <option value="<?= $perfil_usuario;?>" SELECTED><?= $perfil_usuario;?> - ATUAL</option>
                                                    <?php }?>
                                                    <?php if($PerfilUsuario == "ADMINISTRADOR") { ?>
                                                    <option value="ADMINISTRADOR" <?php if($perfil_usuario == "ADMINISTRADOR"){ echo "SELECTED";}?>>ADMINISTRADOR</option>
                                                    <?php }?>

                                                    <?php if($PerfilUsuario == "ADMINISTRADOR") { ?>
                                                    <option value="GERENTE" <?php if($perfil_usuario == "GERENTE"){ echo "SELECTED";}?>>GERENTE DE CONTAS</option>
                                                    <?php }?>

                                                    <?php if($PerfilUsuario == "ADMINISTRADOR" or $PerfilUsuario == "GERENTE") { ?>
                                                    <option value="FULL" <?php if($perfil_usuario == "FULL"){ echo "SELECTED";}?>>FUNCIONÁRIO FULL</option>
                                                    <?php }?>

                                                    <?php if($PerfilUsuario == "ADMINISTRADOR" or $PerfilUsuario == "GERENTE" or $PerfilUsuario == "FULL") { ?>
                                                    <option value="SLIM" <?php if($perfil_usuario == "SLIM"){ echo "SELECTED";}?>>FUNCIONÁRIO SLIM</option>
                                                    <?php }?>

                                                    <option value="FUNCIONARIO" <?php if($perfil_usuario == "FUNCIONARIO"){ echo "SELECTED";}?>>FUNCIONÁRIO</option>

                                                </select>
                                            </div>
                                        </div><!-- Col -->

                                        <div class="col-sm-2">
                                            <div class="form-group">
                                                <label class="control-label">TIPO</label>
                                                <select class="form-control" name="tipo_pessoa">
                                                    
                                                    <option value="Colaborador" <?php if($tipo_pessoa == "Colaborador"){ echo "SELECTED";}?>>Colaborador</option>
                                                    <option value="PJ" <?php if($tipo_pessoa == "PJ"){ echo "SELECTED";}?>>PJ</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-2">
                                            <div class="form-group">
                                                <label class="control-label">STATUS</label>
                                                <select class="form-control" name="status_login">

                                                    <option value="Inativo" <?php if($status_login == ""){ echo "SELECTED";}?>>Inativo</option>
                                                    <option value="Ativo" <?php if($status_login == "Ativo"){ echo "SELECTED";}?>>Ativo</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-4" style="display:none;">
                                            <div class="form-group">
                                                <label class="control-label">PERFIL CREDENCIAL</label>
                                                <select class="form-control" name="perfil_credencial">
                                                    <option label="01 - Carga G1">01 - Carga G1</option>
                                                    <option label="02 - Carga G2">02 - Carga G2</option>
                                                    <option label="03 - Visitante G1">03 - Visitante G1</option>
                                                    <option label="04 - Visitante G2">04 - Visitante G2</option>
                                                    <option label="05 - Visitante G2 ADM">05 - Visitante G2 ADM</option>
                                                    <option label="06 - Prest Serv Portico">06 - Prest Serv Portico</option>
                                                    <option label="07 - Prest Serv G1" value="number:7">07 - Prest Serv G1</option>
                                                    <option label="08 - Prest Serv G2" value="number:8">08 - Prest Serv G2</option>
                                                    <option label="09 - Prest Serv G2 ADM" value="number:9">09 - Prest Serv G2 ADM</option>
                                                    <option label="10 - Prest Serv G1+G2" value="number:10">10 - Prest Serv G1+G2</option>
                                                    <option label="11 - Prest Serv G1+G2+ADM" value="number:11">11 - Prest Serv G1+G2+ADM</option>
                                                    <option label="12 - Residente G1" value="number:12">12 - Residente G1</option>
                                                    <option label="13 - Residente G2" value="number:13">13 - Residente G2</option>
                                                    <option label="14 - Residente G2 ADM" value="number:14">14 - Residente G2 ADM</option>
                                                    <option label="15 - Residente G1+G2" value="number:15">15 - Residente G1+G2</option>
                                                    <option label="19 - Carga Recorrente G2" value="number:16">19 - Carga Recorrente G2</option>
                                                    <option label="16 - Residente G1+G2+ADM" value="number:17">16 - Residente G1+G2+ADM</option>
                                                    <option label="17 - Residente G1 + Atalho" value="number:18">17 - Residente G1 + Atalho</option>
                                                    <option label="18 - Carga Recorrente G1" value="number:19">18 - Carga Recorrente G1</option>
                                                    <option label="20 - Carga Recorrente G1+G2ADM" value="number:20">20 - Carga Recorrente G1+G2ADM</option>
                                                    <option label="21 - Veiculo G2" value="number:21">21 - Veiculo G2</option>
                                                    <option label="22 - CD 1" value="number:22">22 - CD 1</option>
                                                    <option label="23 - Fretado" value="number:23">23 - Fretado</option>
                                                    <option label="24 - Veículo G1" value="number:24">24 - Veículo G1</option>
                                                    <option label="25 - Ceagesp" value="number:25" selected="selected">25 - Ceagesp</option>
                                                    <option label="26 - Residente G3" value="number:26">26 - Residente G3</option>
                                                    <option label="27 - Residente G3 A" value="number:27">27 - Residente G3 A</option>
                                                    <option label="GERAL" value="number:28">GERAL</option>
                                                </select>
                                            </div>

                                        </div>
                                    </div><!-- Row -->
                                    <div class="row">

                                        <div class="col-sm-3">
                                            <div class="form-group">
                                                <label class="control-label">DOCUMENTO</label>
                                                <input type="text" class="form-control" name="document" value="<?= $document;?>">
                                            </div>
                                        </div><!-- Col -->

                                        <div class="col-sm-3" style="display:none;">
                                            <div class="form-group">
                                                <label class="control-label">DEPARTAMENTO</label>
                                                <input type="text" class="form-control" name="department" value="<?= $department;?>">
                                            </div>
                                        </div><!-- Col -->

                                        <div class="col-sm-3">
                                            <div class="form-group">
                                                <label class="control-label">DEPARTAMENTO</label>
                                                <select class="js-example-basic-single w-200 form-control" data-width="100%" name="iddivisao_departamento">

                                                    <option value="" <?php if($iddivisao_departamento == ""){ echo "SELECTED";}?>>Selecione um Departamento</option>

                                                    <?php
                                                    $SelecionaDepartamentos = Seleciona("Division_Departamentos", "WHERE iddivision='".$IdDivision."' AND excluido IS NULL", "ORDER BY nome_departamento ASC");   
                                                    $TotalDepartamentos = mysql_num_rows($SelecionaDepartamentos);
                                                    if($TotalDepartamentos > 0){     
                                                    while($departamentos = mysql_fetch_array($SelecionaDepartamentos)){
                                                    ?>
                                                    <option value="<?= $departamentos['iddivisao_departamento'];?>" <?php if($departamentos['iddivisao_departamento'] == $iddivisao_departamento){ echo "SELECTED";}?>>

                                                        <?= $departamentos['nome_departamento']; ?>

                                                    </option>

                                                    <?php } }else{?>
                                                    <option>Cadastre um Departamento</option>
                                                    <?php }?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-3">
                                            <div class="form-group">
                                                <label class="control-label">ROTA</label>
                                                <select class="form-control" name="idrota" required>
                                                    <?php
                                                    $SelecionaRotaClientes = Seleciona("Rotas_Clientes", "WHERE IdDivision='$IdDivision' AND excluido IS NULL", "ORDER BY padrao DESC");
                                                    while($rota_linha = mysql_fetch_array($SelecionaRotaClientes)){
                                                    ?>

                                                    <option value="<?= $rota_linha['idrota'];?>" <?php if($idrota == $rota_linha['idrota']){ echo "SELECTED";}?>><?= $rota_linha['rota'];?></option>


                                                    <?php }?>

                                                </select>
                                            </div>
                                        </div><!-- Col -->


                                        <div class="col-sm-3">
                                            <div class="form-group">
                                                <label class="control-label">CARGO</label>
                                                <input type="text" class="form-control" name="cargo" value="<?= $cargo;?>">
                                            </div>
                                        </div><!-- Col -->

                                    </div><!-- Row -->
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="control-label">OBSERVAÇÃO</label>
                                                <input type="text" class="form-control" name="observacao" value="<?= $observacao;?>">
                                            </div>
                                        </div><!-- Col -->
                                    </div><!-- Row -->


                                    
                                    <div class="row">

                                    <div class="col-sm-3">
                                        <div class="form-group">
                                            <label class="control-label">PERFIL OPERADOR</label>
                                            <select class="form-control" name="perfil_operador">
                                                <option value="" <?php if($perfil_operador == ""){ echo "SELECTED";}?>>Selecione</option>
                                                <option value="Operador" <?php if($perfil_operador == "Operador"){ echo "SELECTED";}?>>Operador</option>
                                                <option value="Lider" <?php if($perfil_operador == "Lider"){ echo "SELECTED";}?>>Líder</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-sm-3">
                                        <div class="form-group">
                                            <label class="control-label">APROVA OCORRÊNCIA?</label>
                                            <select class="form-control" name="aprovador">
                                                <option value="" <?php if($aprovador == ""){ echo "SELECTED";}?>>Selecione uma opção</option>
                                                <option value="Nao" <?php if($aprovador == "Nao"){ echo "SELECTED";}?>>Não</option>
                                                <option value="Sim" <?php if($aprovador == "Sim"){ echo "SELECTED";}?>>Sim</option>
                                            </select>
                                        </div>
                                    </div>

                                    </div>
                                    

                                    <button type="submit" class="btn btn-success" id="bt-salvar"><i data-feather="save"></i> SALVAR USUÁRIO</button>

                                    <?php                                                         
                                    if($idpessoa > 0 and $PerfilUsuario == "ADMINISTRADOR"){
                                    ?>
                                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#ExcluirPessoa"><i data-feather="trash"></i> EXCLUIR USUÁRIO</button>

                                    <?php
                                    //if($IdDivision == 27){
                                    ?>
                                    <a href="cadastro_usuarios.php?idpessoa=<?= $idpessoa;?>&Sincronizar=Pessoa"><button type="button" class="btn btn-dark"><i data-feather="upload-cloud"></i> SINCRONIZAR INTELBRAS</button></a>
                                    
                                    <?php 
                                //} 
                            } ?>

                                    <a href="listar_usuarios.php"><button type="button" class="btn btn-secondary"><i data-feather="chevrons-left"></i> VOLTAR</button></a>
                                    

                                    <?php                                                         
                                    if($idpessoa > 0 and $PerfilUsuario == "ADMINISTRADOR"){
                                    
                                    $sql = "SELECT SL.idsistema_log, SL.data_cadastro, P.`name` AS cadastrado_por 
                                    FROM Sistema_Logs SL
                                    LEFT JOIN Person as P ON SL.cadastrado_por=P.idpessoa
                                    WHERE SL.categoria = 'Alterar_Usuario' 
                                    AND SL.dados_anterior LIKE '%";
                                    $sql .='"idpessoa\": \"'.$idpessoa.'\"%';
                                    $sql .="' 
                                    ORDER BY SL.idsistema_log DESC 
                                    LIMIT 0,1";

                                    // Executar a consulta
                                    $resultado = mysql_query($sql, $conexao);


                                    // Processar os resultados
                                    if (mysql_num_rows($resultado) > 0) {
                                    $row = mysql_fetch_assoc($resultado);

                                    // Manipule os dados aqui, por exemplo:
                                    $idsistema_log = $row['idsistema_log'];
                                    $data_cadastro = $row['data_cadastro'];
                                    $cadastrado_por = $row['cadastrado_por'];

                                    // Exiba ou use os dados conforme necessário
                                    echo "<br><hr><br><strong>Ultima Atualização: ".date('d/m/Y H:i:s', strtotime($data_cadastro)).", Atualizado Por: $cadastrado_por</strong>";
                                    } 

                                    }?>
                                    
                                </div>
                </form>
                <!-- FIM PESSOAS -->
                <?php }?>

                <?php if($_GET['Aba'] == "Veiculos"){ ?>
                <?php                            
                            
if($_GET['AcaoVeiculo'] == "Adicionar"){
    
$PlacaVeiculo = limpar_string(strtoupper($_POST['PlacaVeiculo']));  
$VagaPrisma = limpar_string(strtoupper($_POST['vaga_prisma']));  
    
    
    
$DadosVeiculos = DadosVeiculo($PlacaVeiculo);

$cor_carro = $DadosVeiculos['5'];
$ano_carro = $DadosVeiculos['0'];
$modelo_carro = $DadosVeiculos['13'];
$ocorrencia_carro = $DadosVeiculos['16'];
$cidade_carro = $DadosVeiculos['14'];
$uf_carro = $DadosVeiculos['17'];   
    
    if($modelo_carro == "LIMITE DE CONSULTA ATINGIDO"){
        $cor_carro = "";
        $ano_carro = "";
        $modelo_carro = "";
        $ocorrencia_carro = "";
        $cidade_carro = "";
        $uf_carro = "";   
    }

$salvar = Salvar("Person_Veiculos", "idperson, idpessoa, placa, data_cadastro, idusuario_cadastro, origem_cadastro, cor, modelo, ano, status, cidade, uf, vaga_prisma", "'$id', '".$_GET['idpessoa']."', '$PlacaVeiculo', '$datacadastro', '$idusuario_cadastro', 'P2PCLIENTE',  '$cor_carro', '$modelo_carro', '$ano_carro', '$ocorrencia_carro', '$cidade_carro', '$uf_carro', '$VagaPrisma'");


//REGRA DE PROCESSAMENTO        

        $datahora_solicitacao_processamento = date('Y-m-d H:i:s');

        $SolicitarProcessamento = Alterar("Person", "datahora_solicitacao_processamento='$datahora_solicitacao_processamento', status_processamento='Solicitado', processamento_acao='AdicionarVeiculo'", "idpessoa", $idpessoa);


        //CRIO NOTIFICAÇÃO 

            $mensagem = "*ADICIONAR VEICULO* \n";
            $mensagem .= "IDSITUATOR:  $idsituator \n";                                    
            $mensagem .= "PLACA:  $PlacaVeiculo \n";
            $mensagem .= "NOME:  $name \n";
            $mensagem .= "CPF:  $cpf \n";            
            $mensagem .= "EMPRESA: $NomeEmpresa\n";    
            $mensagem .= "DATA SOLICITADO: " . date('d/m/Y H:i:s'). " \n";        
            $mensagem .= "CRIADO POR: " . $NomeUsuario . " \n";        
            
                    
            //$EnviarWhatsApp = EnviarWhatsApp('5511940448420', $mensagem, $logo_empresa_atual);

//FIM PROCESSAMENTO  


$redireciona = VerificaSql($salvar, "cadastro_usuarios.php?idpessoa=".$idpessoa."&Aba=Veiculos", "cadastro_usuarios.php?idpessoa=".$idpessoa."&Aba=Veiculos");

}
    
if($_GET["ExcluirVeiculo"] == "Sim"){

 $SelecionaPerson_Veiculos = Seleciona("Person_Veiculos", "WHERE idperson_veiculos='".$_GET['idperson_veiculos']."'", "LIMIT 0,1");
    while($personveiculos = mysql_fetch_array($SelecionaPerson_Veiculos)){

        $placa_base  = $personveiculos['placa'];                
    }

//REGRA DE PROCESSAMENTO        

        $datahora_solicitacao_processamento = date('Y-m-d H:i:s');

        $SolicitarProcessamento = Alterar("Person", "datahora_solicitacao_processamento='$datahora_solicitacao_processamento', status_processamento='Solicitado', processamento_acao='ExcluirVeiculo'", "idpessoa", $idpessoa);


        //CRIO NOTIFICAÇÃO 

            $mensagem = "*REMOVER VEICULO* \n";
            $mensagem .= "IDSITUATOR:  $idsituator \n";                                    
            $mensagem .= "PLACA:  $placa_base \n";
            $mensagem .= "NOME:  $name \n";
            $mensagem .= "CPF:  $cpf \n";            
            $mensagem .= "EMPRESA: $NomeEmpresa\n";    
            $mensagem .= "DATA SOLICITADO: " . date('d/m/Y H:i:s'). " \n";        
            $mensagem .= "CRIADO POR: " . $NomeUsuario . " \n";        
            
                    
            //$EnviarWhatsApp = EnviarWhatsApp('5511940448420', $mensagem, $logo_empresa_atual);

//FIM PROCESSAMENTO 


$deletar = Alterar("Person_Veiculos", "excluido='Sim'", "idperson_veiculos", $_GET['idperson_veiculos']);
    
$redireciona = VerificaSql($deletar, "cadastro_usuarios.php?idpessoa=".$idpessoa."&Aba=Veiculos", "cadastro_usuarios.php?idpessoa=".$idpessoa."&Aba=Veiculos");

}     
                            
?>
                <!-- DADOS VEICULOS -->
                <div class="card-body" id="veiculos">


                    <h6 class="card-title">Dados do(s) Veiculo(s)</h6>

                    <form method="post" action="cadastro_usuarios.php?idpessoa=<?= $idpessoa;?>&Aba=Veiculos&AcaoVeiculo=Adicionar" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label class="control-label">PLACA</label>
                                    <input type="text" class="form-control" id="placa" name="PlacaVeiculo" maxlength="7" minlength="7" required onchange="PlacaBloqueado()">
                                </div>
                            </div><!-- Col -->
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label class="control-label">VAGA/PRISMA</label>
                                    <input type="text" class="form-control" name="vaga_prisma">
                                </div>
                            </div><!-- Col -->
                        </div><!-- Row -->


                        <button type="submit" class="btn btn-success" id="bt-add-veiculo"><i data-feather="save"></i> ADICIONAR VEICULO</button>

                    </form>

                </div>

                <div class="card-body">
                    <h6 class="card-title">Veículos Adicionados</h6>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>PLACA</th>
                                    <th>VAGA/PRISMA</th>
                                    <th>MODELO</th>
                                    <th>ANO</th>
                                    <th>COR</th>
                                    <th>STATUS</th>
                                    <th>LOCALIDADE</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                            $SelecionaVeiculos = Seleciona("Person_Veiculos", "WHERE idpessoa='$idpessoa' AND excluido IS NULL", "");
                                            while($veiculos = mysql_fetch_array($SelecionaVeiculos)){
                                                
                                            ?>
                                <tr>
                                    <td><?= $veiculos['placa'];?></td>
                                    <td><?= $veiculos['vaga_prisma'];?></td>
                                    <td><?= $veiculos['modelo'];?></td>
                                    <td><?= $veiculos['ano'];?></td>
                                    <td><?= $veiculos['cor'];?></td>
                                    <td><?= $veiculos['status'];?></td>
                                    <td><?= $veiculos['cidade'] . " / " . $veiculos['uf'];?></td>
                                    <td>
                                        <a href="cadastro_usuarios.php?idpessoa=<?= $idpessoa;?>&idperson_veiculos=<?= $veiculos['idperson_veiculos'];?>&Aba=Veiculos&ExcluirVeiculo=Sim">
                                            <button type="button" class="btn btn-danger btn-icon" data-toggle="tooltip" data-placement="top" title="Excluir Veiculo">
                                                <i data-feather="trash"></i>
                                            </button>
                                        </a>
                                    </td>
                                </tr>
                                <?php }?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- FIM VEICULOS -->
                <?php }?>

                <?php if($_GET['Aba'] == "Credenciais"){ ?>

                <?php
                            if($_GET['AtualzarCredencial'] == "Alterar"){

                            $campos_alterar = "name='$name', document='$document', cpf='$cpf', phone='$phone', email='$email', department='$department', empresa_alocada='$empresa_alocada', perfil_usuario='$perfil_usuario', perfil_credencial='$perfil_credencial', cargo='$cargo', observacao='$observacao'";

                            $atualizar = Alterar($tabela, $campos_alterar, "idpessoa", $idpessoa);

                            $redireciona = VerificaSql($atualizar, "cadastro_usuarios.php?idpessoa=".$idpessoa."", "cadastro_usuarios.php?idpessoa=".$idpessoa."");

                            }                                                                    
                            ?>

                <!-- DADOS CREDENCIAIS -->
                <div class="card-body" id="credenciais">


                    <h6 class="card-title">Dados da Credencial</h6>

                    
<input type="hidden" name="temporario" id="temporario">

                    <div class="row">
                        <div class="col-sm-2">
                            <div class="form-group">
                                <label class="control-label">LIBERADO ACESSO?</label>
                                <select class="form-control" name="credencia_liberado" id="credencia_liberado">
                                    <option value="Sim">SIM</option>
                                    <option value="Nao">NÃO</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-group">
                                <label class="control-label">TIPO</label>
                                <select class="form-control" name="credencial_tipo" id="credencial_tipo">
                                    <option value="Cartao">CARTÃO</option>
                                </select>
                            </div>
                        </div><!-- Col -->
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label class="control-label">CÓDIGO W <i data-feather="info" style="padding-left: 3px; margin-left: 20px; cursor: pointer;" data-toggle="modal" data-target="#ModeloCartao"></i></label>
                                <input type="text" class="form-control" name="crendecial_codigow" id="crendecial_codigow" value="<?= $crendecial_codigow;?>" Onchange="VericaCodW('crendecial_codigow', 'Cartao');" data-inputmask-alias="999,99999">
                            </div>
                        </div><!-- Col -->

                        <div class="col-sm-3">
                            <div class="form-group">
                                <label class="control-label">RFID</label>
                                <input type="text" class="form-control" name="crendecial_rfid" id="crendecial_rfid" value="<?= $crendecial_rfid;?>" Onchange="VericaCodW('crendecial_rfid', 'Rfid');">
                            </div>
                        </div><!-- Col -->

                        <div class="col-sm-2" <?php if($IdSituator_Liberado != "Liberado"){ ?> style="display: none;" <?php }?>>
                            <div class="form-group">
                                <label class="control-label">IDSITUATOR</label>
                                <input type="text" class="form-control" name="idsituator" id="idsituator" value="<?= $idsituator;?>">
                            </div>
                        </div><!-- Col -->

                    </div><!-- Row -->
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="control-label">INICIO CREDENCIAL</label>
                                <input type="date" class="form-control" name="crendencial_datainicio" id="crendencial_datainicio" value="<?= $crendencial_datainicio;?>">
                            </div>
                        </div><!-- Col -->

                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="control-label">FIM CREDENCIAL</label>
                                <input type="date" class="form-control" name="crendencial_datafim" id="crendencial_datafim" value="<?= $crendencial_datafim;?>">
                            </div>
                        </div><!-- Col -->

                    </div><!-- Row -->

                    <h6 class="card-title">Dados Novo Cartão 13,56Mhz</h6>
                    <div class="row" style="display: none;">

                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="control-label">A:* (Obrigatório)</label>
                                <input type="text" class="form-control" name="cartao_1356_a" id="cartao_1356_a" value="<?= $cartao_1356_a;?>" minlength="14" maxlength="14" data-inputmask-alias="99999999999999">
                            </div>
                        </div><!-- Col -->

                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="control-label">S:* (Obrigatório)</label>
                                <input type="text" class="form-control" name="cartao_1356_s" id="cartao_1356_s" value="<?= $cartao_1356_s;?>" minlength="10" maxlength="10">
                            </div>
                        </div><!-- Col -->


                        <div class="col-sm-4">
                            <div class="form-group">
                                <label class="control-label">W:</label>
                                <input type="text" class="form-control" name="cartao_1356_w" id="cartao_1356_w" value="<?= $cartao_1356_w;?>" data-inputmask-alias="999,99999">
                            </div>
                        </div><!-- Col -->

                    </div><!-- Row -->



                </div>
                <div class="card-body">
                    <h6 class="card-title" style="display: none;">Acesso Credencial</h6>
                    <div class="table-responsive" <?php if($_SESSION['usuarioID'] != "1352"){ ?>style="display: none;" <?php }?>>
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>DIA SEMANA</th>
                                    <th>HORA INICIO</th>
                                    <th>HORA FIM</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="checkbox" id="credencial_domingo" name="credencial_domingo" onchange="OnCheckboxValue('credencial_domingo');" <?php if($credencial_domingo == "Sim"){ echo "value='Sim' checked"; } ?>></td>
                                    <td>DOMINGO</td>
                                    <td><input type="time" class="form-control" name="credencial_domingo_horainicio" id="credencial_domingo_horainicio" value="<?= $credencial_domingo_horainicio;?>"></td>
                                    <td><input type="time" class="form-control" name="credencial_domingo_horafim" id="credencial_domingo_horafim" value="<?= $credencial_domingo_horafim;?>"></td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" id="credencial_segunda" name="credencial_segunda" onchange="OnCheckboxValue('credencial_segunda');" <?php if($credencial_segunda == "Sim"){ echo "value='Sim' checked"; } ?>></td>
                                    <td>SEGUNDA</td>
                                    <td><input type="time" class="form-control" name="credencial_segunda_horainicio" id="credencial_segunda_horainicio" value="<?= $credencial_segunda_horainicio;?>"></td>
                                    <td><input type="time" class="form-control" name="credencial_segunda_horafim" id="credencial_segunda_horafim" value="<?= $credencial_segunda_horafim;?>"></td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" id="credencial_terca" name="credencial_terca" onchange="OnCheckboxValue('credencial_terca');" <?php if($credencial_terca == "Sim"){ echo "value='Sim' checked"; } ?>></td>
                                    <td>TERÇA</td>
                                    <td><input type="time" class="form-control" name="credencial_terca_horainicio" id="credencial_terca_horainicio" value="<?= $credencial_terca_horainicio;?>"></td>
                                    <td><input type="time" class="form-control" name="credencial_terca_horafim" id="credencial_terca_horafim" value="<?= $credencial_terca_horafim;?>"></td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" id="credencial_quarta" name="credencial_quarta" onchange="OnCheckboxValue('credencial_quarta');" <?php if($credencial_quarta == "Sim"){ echo "value='Sim' checked"; } ?>></td>
                                    <td>QUARTA</td>
                                    <td><input type="time" class="form-control" name="credencial_quarta_horainicio" id="credencial_quarta_horainicio" value="<?= $credencial_quarta_horainicio;?>"></td>
                                    <td><input type="time" class="form-control" name="credencial_quarta_horafim" id="credencial_quarta_horafim" value="<?= $credencial_quarta_horafim;?>"></td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" id="credencial_quinta" name="credencial_quinta" onchange="OnCheckboxValue('credencial_quinta');" <?php if($credencial_quinta == "Sim"){ echo "value='Sim' checked"; } ?>></td>
                                    <td>QUINTA</td>
                                    <td><input type="time" class="form-control" name="credencial_quinta_horainicio" id="credencial_quinta_horainicio" value="<?= $credencial_quinta_horainicio;?>"></td>
                                    <td><input type="time" class="form-control" name="credencial_quinta_horafim" id="credencial_quinta_horafim" value="<?= $credencial_quinta_horafim;?>"></td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" id="credencial_sexta" name="credencial_sexta" onchange="OnCheckboxValue('credencial_sexta');" <?php if($credencial_sexta == "Sim"){ echo "value='Sim' checked"; } ?>></td>
                                    <td>SEXTA</td>
                                    <td><input type="time" class="form-control" name="credencial_sexta_horainicio" id="credencial_sexta_horainicio" value="<?= $credencial_sexta_horainicio;?>"></td>
                                    <td><input type="time" class="form-control" name="credencial_sexta_horafim" id="credencial_sexta_horafim" value="<?= $credencial_sexta_horafim;?>"></td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" id="credencial_sabado" name="credencial_sabado" onchange="OnCheckboxValue('credencial_sabado');" <?php if($credencial_sabado == "Sim"){ echo "value='Sim' checked"; } ?>></td>
                                    <td>SABADO</td>
                                    <td><input type="time" class="form-control" name="credencial_sabado_horainicio" id="credencial_sabado_horainicio" value="<?= $credencial_sabado_horainicio;?>"></td>
                                    <td><input type="time" class="form-control" name="credencial_sabado_horafim" id="credencial_sabado_horafim" value="<?= $credencial_sabado_horafim;?>"></td>
                                </tr>
                            </tbody>
                        </table>

                    </div>
                    <div class="row">

                        <button type="button" class="btn btn-success" onclick="AlterarDadosCredencial('<?= $_REQUEST['idpessoa'];?>');"><i data-feather="save"></i> SALVAR DADOS CREDENCIAL </button>

                    </div>
                </div>


                <script type="text/javascript">
                    function AlterarDadosCredencial(idpessoa) {


                        var credencia_liberado = document.getElementById("credencia_liberado").value;
                        var credencial_tipo = document.getElementById("credencial_tipo").value;
                        var crendecial_codigow = document.getElementById("crendecial_codigow").value;
                        var crendecial_rfid = document.getElementById("crendecial_rfid").value;
                        var crendencial_datainicio = document.getElementById("crendencial_datainicio").value;
                        var crendencial_datafim = document.getElementById("crendencial_datafim").value;
                        var credencial_domingo_horainicio = document.getElementById("credencial_domingo_horainicio").value;
                        var credencial_domingo_horafim = document.getElementById("credencial_domingo_horafim").value;
                        var credencial_segunda_horainicio = document.getElementById("credencial_segunda_horainicio").value;
                        var credencial_segunda_horafim = document.getElementById("credencial_segunda_horafim").value;
                        var credencial_terca_horainicio = document.getElementById("credencial_terca_horainicio").value;
                        var credencial_terca_horafim = document.getElementById("credencial_terca_horafim").value;
                        var credencial_quarta_horainicio = document.getElementById("credencial_quarta_horainicio").value;
                        var credencial_quarta_horafim = document.getElementById("credencial_quarta_horafim").value;
                        var credencial_quinta_horainicio = document.getElementById("credencial_quinta_horainicio").value;
                        var credencial_quinta_horafim = document.getElementById("credencial_quinta_horafim").value;
                        var credencial_sexta_horainicio = document.getElementById("credencial_sexta_horainicio").value;
                        var credencial_sexta_horafim = document.getElementById("credencial_sexta_horafim").value;
                        var credencial_sabado_horainicio = document.getElementById("credencial_sabado_horainicio").value;
                        var credencial_sabado_horafim = document.getElementById("credencial_sabado_horafim").value;
                        var idsituator = document.getElementById("idsituator").value;
                        var credencial_domingo = document.getElementById("credencial_domingo").value;
                        var credencial_segunda = document.getElementById("credencial_segunda").value;
                        var credencial_terca = document.getElementById("credencial_terca").value;
                        var credencial_quarta = document.getElementById("credencial_quarta").value;
                        var credencial_quinta = document.getElementById("credencial_quinta").value;
                        var credencial_sexta = document.getElementById("credencial_sexta").value;
                        var credencial_sabado = document.getElementById("credencial_sabado").value;
                        var cartao_1356_a = document.getElementById("cartao_1356_a").value;
                        var cartao_1356_s = document.getElementById("cartao_1356_s").value;
                        var cartao_1356_w = document.getElementById("cartao_1356_w").value;


                        var dadosajax = {

                            'idpessoa': idpessoa,
                            'status_login': "<?= $status_login;?>",
                            'origem': "Credencial",
                            'credencia_liberado': credencia_liberado,
                            'credencial_tipo': credencial_tipo,
                            'crendecial_codigow': crendecial_codigow,
                            'crendecial_rfid': crendecial_rfid,
                            'crendencial_datainicio': crendencial_datainicio,
                            'crendencial_datafim': crendencial_datafim,
                            'credencial_domingo_horainicio': credencial_domingo_horainicio,
                            'credencial_domingo_horafim': credencial_domingo_horafim,
                            'credencial_segunda_horainicio': credencial_segunda_horainicio,
                            'credencial_segunda_horafim': credencial_segunda_horafim,
                            'credencial_terca_horainicio': credencial_terca_horainicio,
                            'credencial_terca_horafim': credencial_terca_horafim,
                            'credencial_quarta_horainicio': credencial_quarta_horainicio,
                            'credencial_quarta_horainicio': credencial_quarta_horainicio,
                            'credencial_quinta_horainicio': credencial_quinta_horainicio,
                            'credencial_quinta_horafim': credencial_quinta_horafim,
                            'credencial_sexta_horainicio': credencial_sexta_horainicio,
                            'credencial_sexta_horafim': credencial_sexta_horafim,
                            'credencial_sabado_horainicio': credencial_sabado_horainicio,
                            'credencial_sabado_horafim': credencial_sabado_horafim,
                            'idsituator': idsituator,
                            'credencial_domingo': credencial_domingo,
                            'credencial_segunda': credencial_segunda,
                            'credencial_terca': credencial_terca,
                            'credencial_quarta': credencial_quarta,
                            'credencial_quinta': credencial_quinta,
                            'credencial_sexta': credencial_sexta,
                            'credencial_sabado': credencial_sabado,
                            'cartao_1356_a': cartao_1356_a,
                            'cartao_1356_s': cartao_1356_s,
                            'cartao_1356_w': cartao_1356_w
                        };
                        pageurl = '<?= $DiretorioVirtual_Ajax_Usuarios;?>ajax_alterar_usuarios.php';
                        $.ajax({

                            url: pageurl,
                            data: dadosajax,
                            type: 'POST',
                            cache: false,
                            error: function() {},
                            //retorna o resultado da pagina para onde enviamos os dados
                            success: function(result) {
                                //se foi inserido com sucesso
                                if ($.trim(result) == '1') {

                                    alert("Dados de Credencial Salvo com Sucesso!");
                                }
                                //se foi um erro
                                else {
                                    alert("Ocorreu um erro ao inserir o seu registo!");
                                }

                            }
                        });

                    }
                </script>

                <!-- FIM CREDENCIAIS -->
                <?php }?>


                <?php if($_GET['Aba'] == "Acesso"){ ?>
                <!-- DADOS ACESSO -->
                <div class="card-body" id="acesso">


                    <h6 class="card-title">Dados de Acesso ao Sistema</h6>
                    <div class="row">
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label class="control-label">LIBERADO ACESSO?</label>
                                <select class="form-control" name="config_liberadoacesso" id="config_liberadoacesso">
                                    <option value="" <?php if($config_liberadoacesso == ""){ echo "SELECTED";}?>>NÃO</option>
                                    <option value="Sim" <?php if($config_liberadoacesso == "Sim"){ echo "SELECTED";}?>>SIM</option>
                                </select>
                            </div>
                        </div><!-- Col -->
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label class="control-label">DUPLA VERIFICAÇÃO?</label>
                                <select class="form-control" name="config_duplaverificacao" id="config_duplaverificacao">
                                    <option value="" <?php if($config_duplaverificacao == ""){ echo "SELECTED";}?>>NÃO</option>
                                    <option value="Sim" <?php if($config_duplaverificacao == "Sim"){ echo "SELECTED";}?>>SIM</option>
                                </select>
                            </div>
                        </div><!-- Col -->

                        <div class="col-sm-3">
                            <div class="form-group">
                                <label class="control-label">TROCAR SENHA</label>
                                <select class="form-control" name="trocarsenha" id="trocarsenha">
                                    <option value="" <?php if($trocarsenha == ""){ echo "selected";}?>>NÃO</option>
                                    <option value="Sim" <?php if($trocarsenha == "Sim"){ echo "selected";}?>>SIM</option>
                                </select>
                            </div>
                        </div><!-- Col -->

                        <div class="col-sm-3">
                            <div class="form-group">
                                <label class="control-label">SENHA TEMPORÁRIA</label>
                                <input type="password" class="form-control" name="senha_pessoa" id="senha_pessoa">
                            </div>
                        </div><!-- Col -->


                    </div><!-- Row -->
                    
                    
                    
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="control-label">INICIO ACESSO SISTEMA</label>
                                <input type="date" class="form-control" name="config_datainicio" id="config_datainicio" value="<?= $config_datainicio;?>">
                            </div>
                        </div><!-- Col -->

                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="control-label">FIM ACESSO SISTEMA</label>
                                <input type="date" class="form-control" name="config_datafim" id="config_datafim" value="<?= $config_datafim;?>">
                            </div>
                        </div><!-- Col -->

                    </div><!-- Row -->
                    
                    <?php
                    //if($PerfilMaster == 1){   
                    //if($PerfilUsuario == "ADMINISTRADOR"){                            
                    ?>
                    <h6 class="card-title">Módulos do Sistema</h6>
                    <div class="row" style="display: <?php if($PerfilUsuario == "ADMINISTRADOR"){ echo "flex";}else{ echo "none";} ?>" >
                        <div class="col-sm-2">
                            <div class="form-group">
                                <label class="control-label">CLIENTE</label>
                                <select class="form-control" name="sistema_cliente" id="sistema_cliente">
                                    <option value="" <?php if($sistema_cliente == ""){ echo "SELECTED";}?>>NÃO</option>
                                    <option value="Sim" <?php if($sistema_cliente == "Sim"){ echo "SELECTED";}?>>SIM</option>
                                </select>
                            </div>
                        </div><!-- Col -->
                        <div class="col-sm-2">
                            <div class="form-group">
                                <label class="control-label">IMA</label>
                                <select class="form-control" name="sistema_ima" id="sistema_ima">
                                    <option value="" <?php if($sistema_ima == ""){ echo "SELECTED";}?>>NÃO</option>
                                    <option value="Sim" <?php if($sistema_ima == "Sim"){ echo "SELECTED";}?>>SIM</option>
                                </select>
                            </div>
                        </div><!-- Col -->

                        <div class="col-sm-2">
                            <div class="form-group">
                                <label class="control-label">OPERAÇÃO</label>
                                <select class="form-control" name="sistema_operacao" id="sistema_operacao">
                                    <option value="" <?php if($sistema_operacao == ""){ echo "SELECTED";}?>>NÃO</option>
                                    <option value="Sim" <?php if($sistema_operacao == "Sim"){ echo "SELECTED";}?>>SIM</option>
                                </select>
                            </div>
                        </div><!-- Col -->

                        <div class="col-sm-2">
                            <div class="form-group">
                                <label class="control-label">BI</label>
                                <select class="form-control" name="sistema_bi" id="sistema_bi">
                                    <option value="" <?php if($sistema_bi == ""){ echo "SELECTED";}?>>NÃO</option>
                                    <option value="Sim" <?php if($sistema_bi == "Sim"){ echo "SELECTED";}?>>SIM</option>
                                </select>
                            </div>
                        </div><!-- Col -->

                        <div class="col-sm-2">
                            <div class="form-group">
                                <label class="control-label">CONDOMINIO</label>
                                <select class="form-control" name="sistema_condominio" id="sistema_condominio">
                                    <option value="" <?php if($sistema_condominio == ""){ echo "SELECTED";}?>>NÃO</option>
                                    <option value="Sim" <?php if($sistema_condominio == "Sim"){ echo "SELECTED";}?>>SIM</option>
                                </select>
                            </div>
                        </div><!-- Col -->


                    </div><!-- Row -->
                    <?php //}?>
                    
                </div>

                <div class="card-body">
                    <h6 class="card-title">Acesso ao Sistema</h6>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>DIA SEMANA</th>
                                    <th>HORA INICIO</th>
                                    <th>HORA FIM</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="checkbox" id="config_domingo" name="config_domingo" onchange="OnCheckboxValue('config_domingo');"  <?php if($config_domingo == "Sim"){ echo "value='Sim' checked"; } ?>></td>
                                    <td>DOMINGO</td>
                                    <td><input type="time" class="form-control" name="config_domingo_horainicio" id="config_domingo_horainicio" value="<?= $config_domingo_horainicio;?>"></td>
                                    <td><input type="time" class="form-control" id="config_domingo_horafim" name="config_domingo_horafim" value="<?= $config_domingo_horafim;?>"></td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" id="config_segunda" name="config_segunda" onchange="OnCheckboxValue('config_segunda');" <?php if($config_segunda == "Sim"){ echo "value='Sim' checked"; } ?>></td>
                                    <td>SEGUNDA</td>
                                    <td><input type="time" class="form-control" name="config_segunda_horainicio" id="config_segunda_horainicio" value="<?= $config_segunda_horainicio;?>"></td>
                                    <td><input type="time" class="form-control" name="config_segunda_horafim" id="config_segunda_horafim" value="<?= $config_segunda_horafim;?>"></td>
                                </tr>
                                <tr>

                                    <td><input type="checkbox" id="config_terca" name="config_terca" onchange="OnCheckboxValue('config_terca');" <?php if($config_terca == "Sim"){ echo "value='Sim' checked"; } ?>></td>
                                    <td>TERÇA</td>
                                    <td><input type="time" class="form-control" name="config_terca_horainicio" id="config_terca_horainicio" value="<?= $config_terca_horainicio;?>"></td>
                                    <td><input type="time" class="form-control" name="config_terca_horafim" id="config_terca_horafim" value="<?= $config_terca_horafim;?>"></td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" id="config_quarta" name="config_quarta" onchange="OnCheckboxValue('config_quarta');" <?php if($config_quarta == "Sim"){ echo "value='Sim' checked"; } ?>></td>
                                    <td>QUARTA</td>
                                    <td><input type="time" class="form-control" name="config_quarta_horainicio" id="config_quarta_horainicio" value="<?= $config_quarta_horainicio;?>"></td>
                                    <td><input type="time" class="form-control" name="config_quarta_horafim" id="config_quarta_horafim" value="<?= $config_quarta_horafim;?>"></td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" id="config_quinta" name="config_quinta" onchange="OnCheckboxValue('config_quinta');" <?php if($config_quinta == "Sim"){ echo "value='Sim' checked"; } ?>></td>
                                    <td>QUINTA</td>
                                    <td><input type="time" class="form-control" name="config_quinta_horainicio" id="config_quinta_horainicio" value="<?= $config_quinta_horainicio;?>"></td>
                                    <td><input type="time" class="form-control" name="config_quinta_horafim" id="config_quinta_horafim" value="<?= $config_quinta_horafim;?>"></td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" id="config_sexta" name="config_sexta" onchange="OnCheckboxValue('config_sexta');" <?php if($config_sexta == "Sim"){ echo "value='Sim' checked"; } ?>></td>
                                    <td>SEXTA</td>
                                    <td><input type="time" class="form-control" name="config_sexta_horainicio" id="config_sexta_horainicio" value="<?= $config_sexta_horainicio;?>"></td>
                                    <td><input type="time" class="form-control" name="config_sexta_horafim" id="config_sexta_horafim" value="<?= $config_sexta_horafim;?>"></td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" id="config_sabado" name="config_sabado" onchange="OnCheckboxValue('config_sabado');" <?php if($config_sabado == "Sim"){ echo "value='Sim' checked"; } ?>></td>
                                    <td>SABADO</td>
                                    <td><input type="time" class="form-control" name="config_sabado_horainicio" id="config_sabado_horainicio" value="<?= $config_sabado_horainicio;?>"></td>
                                    <td><input type="time" class="form-control" name="config_sabado_horafim" id="config_sabado_horafim" value="<?= $config_sabado_horafim;?>"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="row">

                        <button type="button" class="btn btn-success" onclick="AlterarDadosConfiguracao('<?= $_REQUEST['idpessoa'];?>');"><i data-feather="save"></i> SALVAR DADOS DE CONFIGURAÇÃO DO USUÁRIO</button>

                    </div>
                </div>

                <script type="text/javascript">

                    function AlterarDadosConfiguracao(idpessoa) {

                        var trocarsenha = document.getElementById("trocarsenha").value;

                        var config_liberadoacesso = document.getElementById("config_liberadoacesso").value;
                        var config_duplaverificacao = document.getElementById("config_duplaverificacao").value;
                        var config_datainicio = document.getElementById("config_datainicio").value;
                        var config_datafim = document.getElementById("config_datafim").value;
                        var config_domingo_horainicio = document.getElementById("config_domingo_horainicio").value;
                        var config_domingo_horafim = document.getElementById("config_domingo_horafim").value;
                        var config_segunda_horainicio = document.getElementById("config_segunda_horainicio").value;
                        var config_segunda_horafim = document.getElementById("config_segunda_horafim").value;
                        var config_terca_horainicio = document.getElementById("config_terca_horainicio").value;
                        var config_terca_horafim = document.getElementById("config_terca_horafim").value;
                        var config_quarta_horainicio = document.getElementById("config_quarta_horainicio").value;
                        var config_quarta_horafim = document.getElementById("config_quarta_horafim").value;
                        var config_quinta_horainicio = document.getElementById("config_quinta_horainicio").value;
                        var config_quinta_horafim = document.getElementById("config_quinta_horafim").value;
                        var config_sexta_horainicio = document.getElementById("config_sexta_horainicio").value;
                        var config_sexta_horafim = document.getElementById("config_sexta_horafim").value;
                        var config_sabado_horainicio = document.getElementById("config_sabado_horainicio").value;
                        var config_sabado_horafim = document.getElementById("config_sabado_horafim").value;
                        
                        var senha_pessoa = document.getElementById("senha_pessoa").value;

                        var config_domingo = document.getElementById("config_domingo").value;
                        var config_segunda = document.getElementById("config_segunda").value;
                        var config_terca = document.getElementById("config_terca").value;
                        var config_quarta = document.getElementById("config_quarta").value;
                        var config_quinta = document.getElementById("config_quinta").value;
                        var config_sexta = document.getElementById("config_sexta").value;
                        var config_sabado = document.getElementById("config_sabado").value;
                        
                        
                        var sistema_cliente = document.getElementById("sistema_cliente").value;
                        var sistema_ima = document.getElementById("sistema_ima").value;
                        var sistema_bi = document.getElementById("sistema_bi").value;
                        var sistema_operacao = document.getElementById("sistema_operacao").value;
                        var sistema_condominio = document.getElementById("sistema_condominio").value;
                        

                        var dadosajax = {

                            'idpessoa': idpessoa,
                            'origem': "Configuracao",
                            'trocarsenha': trocarsenha,
                            'config_liberadoacesso': config_liberadoacesso,
                            'config_duplaverificacao': config_duplaverificacao,
                            'config_datainicio': config_datainicio,
                            'config_datafim': config_datafim,
                            'config_domingo_horainicio': config_domingo_horainicio,
                            'config_domingo_horafim': config_domingo_horainicio,
                            'config_segunda_horainicio': config_segunda_horainicio,
                            'config_segunda_horafim': config_segunda_horafim,
                            'config_terca_horainicio': config_terca_horainicio,
                            'config_terca_horafim': config_terca_horafim,
                            'config_quarta_horainicio': config_quarta_horainicio,
                            'config_quarta_horafim': config_quarta_horafim,
                            'config_quinta_horainicio': config_quinta_horainicio,
                            'config_quinta_horafim': config_quinta_horafim,
                            'config_sexta_horainicio': config_sexta_horainicio,
                            'config_sexta_horafim': config_sexta_horafim,
                            'config_sabado_horainicio': config_sabado_horainicio,
                            'config_sabado_horafim': config_sabado_horafim,
                            'senha_pessoa': senha_pessoa,
                            'config_domingo': config_domingo,
                            'config_segunda': config_segunda,
                            'config_terca': config_terca,
                            'config_quarta': config_quarta,
                            'config_quinta': config_quinta,
                            'config_sexta': config_sexta,
                            'config_sabado': config_sabado,

                            
                            'sistema_cliente': sistema_cliente,
                            'sistema_ima': sistema_ima,
                            'sistema_bi': sistema_bi,
                            'sistema_bi': sistema_bi,
                            'sistema_condominio': sistema_condominio,
                            'sistema_operacao': sistema_operacao,
                            
                        };
                        pageurl = '<?= $DiretorioVirtual_Ajax_Usuarios;?>ajax_alterar_usuarios.php';
                        $.ajax({

                            url: pageurl,
                            data: dadosajax,
                            type: 'POST',
                            cache: false,
                            error: function() {},
                            //retorna o resultado da pagina para onde enviamos os dados
                            success: function(result) {
                                //se foi inserido com sucesso
                                if ($.trim(result) == '1') {

                                    alert("Dados de Acesso Salvo com Sucesso!");
                                }
                                //se foi um erro
                                else {
                                    alert("Ocorreu um erro ao inserir o seu registo!");
                                }

                            }
                        });

                    }
                </script>

                <!-- FIM ACESSO -->
                <?php }?>

                <?php if($_GET['Aba'] == "Sistema"){ ?>
                <!-- DADOS SISTEMA -->

                <div class="card-body">
                    <h6 class="card-title">Programas do Sistema</h6>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>MÓDULO</th>
                                    <th>PROGRAMA</th>
                                    <th style="text-align: center;">VISUALIZAR</th>
                                    <th style="text-align: center;">INSERIR</th>
                                    <th style="text-align: center;">ALTERAR</th>
                                    <th style="text-align: center;">COMPLETA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>CONVITES</td>
                                    <td>NOVO CONVITE</td>
                                    <td style="text-align: center;"><input type="checkbox" class="form-check-input" checked style=" margin: 0px;"></td>
                                    <td style="text-align: center;"><input type="checkbox" class="form-check-input" style=" margin: 0px;"></td>
                                    <td style="text-align: center;"><input type="checkbox" class="form-check-input" style=" margin: 0px;"></td>
                                    <td style="text-align: center;"><input type="checkbox" class="form-check-input" style=" margin: 0px;"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- FIM SISTEMA -->
                <?php }?>

                <?php if($_GET['Aba'] == "Convites"){ ?>
                <!-- DADOS CONVITES -->
                <div class="card-body">
                    <h6 class="card-title">Geração de Convites</h6>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>DIA SEMANA</th>
                                    <th>HORA INICIO</th>
                                    <th>HORA FIM</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="checkbox" id="convites_domingo" name="convites_domingo" onchange="OnCheckboxValue('convites_domingo');" <?php if($convites_domingo == "Sim"){ echo "value='Sim' checked"; } ?>></td>
                                    <td>DOMINGO</td>
                                    <td><input type="time" class="form-control" name="convites_domingo_horainicio" id="convites_domingo_horainicio" value="<?= $convites_domingo_horainicio;?>"></td>
                                    <td><input type="time" class="form-control" name="convites_domingo_horafim" id="convites_domingo_horafim" value="<?= $convites_domingo_horafim;?>"></td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" id="convites_segunda" name="convites_segunda" onchange="OnCheckboxValue('convites_segunda');" <?php if($convites_segunda == "Sim"){ echo "value='Sim' checked"; } ?>></td>
                                    <td>SEGUNDA</td>
                                    <td><input type="time" class="form-control" name="convites_segunda_horainicio" id="convites_segunda_horainicio" value="<?= $convites_segunda_horainicio;?>"></td>
                                    <td><input type="time" class="form-control" name="convites_segunda_horafim" id="convites_segunda_horafim" value="<?= $convites_segunda_horafim;?>"></td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" id="convites_terca" name="convites_terca" onchange="OnCheckboxValue('convites_terca');" <?php if($convites_terca == "Sim"){ echo "value='Sim' checked"; } ?>></td>
                                    <td>TERÇA</td>
                                    <td><input type="time" class="form-control" name="convites_terca_horainicio" id="convites_terca_horainicio" value="<?= $convites_terca_horainicio;?>"></td>
                                    <td><input type="time" class="form-control" name="convites_terca_horafim" id="convites_terca_horafim" value="<?= $convites_terca_horafim;?>"></td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" id="convites_quarta" name="convites_quarta" onchange="OnCheckboxValue('convites_quarta');" <?php if($convites_quarta == "Sim"){ echo "value='Sim' checked"; } ?>></td>
                                    <td>QUARTA</td>
                                    <td><input type="time" class="form-control" name="convites_quarta_horainicio" id="convites_quarta_horainicio" value="<?= $convites_quarta_horainicio;?>"></td>
                                    <td><input type="time" class="form-control" name="convites_quarta_horafim" id="convites_quarta_horafim" value="<?= $convites_quarta_horafim;?>"></td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" id="convites_quinta" name="convites_quinta" onchange="OnCheckboxValue('convites_quinta');" <?php if($convites_quinta == "Sim"){ echo "value='Sim' checked"; } ?>></td>
                                    <td>QUINTA</td>
                                    <td><input type="time" class="form-control" name="convites_quinta_horainicio" id="convites_quinta_horainicio" value="<?= $convites_quinta_horainicio;?>"></td>
                                    <td><input type="time" class="form-control" name="convites_quinta_horafim" id="convites_quinta_horafim" value="<?= $convites_quinta_horafim;?>"></td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" id="convites_sexta" name="convites_sexta" onchange="OnCheckboxValue('convites_sexta');" <?php if($convites_sexta == "Sim"){ echo "value='Sim' checked"; } ?>></td>
                                    <td>SEXTA</td>
                                    <td><input type="time" class="form-control" name="convites_sexta_horainicio" id="convites_sexta_horainicio" value="<?= $convites_sexta_horainicio;?>"></td>
                                    <td><input type="time" class="form-control" name="convites_sexta_horafim" id="convites_sexta_horafim" value="<?= $convites_sexta_horafim;?>"></td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" id="convites_sabado" name="convites_sabado" onchange="OnCheckboxValue('convites_sabado');" <?php if($convites_sabado == "Sim"){ echo "value='Sim' checked"; } ?>></td>
                                    <td>SABADO</td>
                                    <td><input type="time" class="form-control" name="convites_sabado_horainicio" id="convites_sabado_horainicio" value="<?= $convites_sabado_horainicio;?>"></td>
                                    <td><input type="time" class="form-control" name="convites_sabado_horafim" id="convites_sabado_horafim" value="<?= $convites_sabado_horafim;?>"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="row">

                        <button type="button" class="btn btn-success" onclick="AlterarDadosConvites('<?= $_REQUEST['idpessoa'];?>');"><i data-feather="save"></i> SALVAR DADOS GERAÇÃO DE CONVITES</button>

                    </div>
                </div>

                <script type="text/javascript">
                    function AlterarDadosConvites(idpessoa) {

                        var convites_domingo_horainicio = document.getElementById("convites_domingo_horainicio").value;
                        var convites_domingo_horafim = document.getElementById("convites_domingo_horafim").value;
                        var convites_segunda_horainicio = document.getElementById("convites_segunda_horainicio").value;
                        var convites_segunda_horafim = document.getElementById("convites_segunda_horafim").value;
                        var convites_terca_horainicio = document.getElementById("convites_terca_horainicio").value;
                        var convites_terca_horafim = document.getElementById("convites_terca_horafim").value;
                        var convites_quarta_horainicio = document.getElementById("convites_quarta_horainicio").value;
                        var convites_quarta_horafim = document.getElementById("convites_quarta_horafim").value;
                        var convites_quinta_horainicio = document.getElementById("convites_quinta_horainicio").value;
                        var convites_quinta_horafim = document.getElementById("convites_quinta_horafim").value;
                        var convites_sexta_horainicio = document.getElementById("convites_sexta_horainicio").value;
                        var convites_sexta_horafim = document.getElementById("convites_sexta_horafim").value;
                        var convites_sabado_horainicio = document.getElementById("convites_sabado_horainicio").value;
                        var convites_sabado_horafim = document.getElementById("convites_sabado_horafim").value;

                        var convites_domingo = document.getElementById("convites_domingo").value;
                        var convites_segunda = document.getElementById("convites_segunda").value;
                        var convites_terca = document.getElementById("convites_terca").value;
                        var convites_quarta = document.getElementById("convites_quarta").value;
                        var convites_quinta = document.getElementById("convites_quinta").value;
                        var convites_sexta = document.getElementById("convites_sexta").value;
                        var convites_sabado = document.getElementById("convites_sabado").value;



                        var dadosajax = {

                            'idpessoa': idpessoa,
                            'origem': "Convites",
                            'convites_domingo_horainicio': convites_domingo_horainicio,
                            'convites_domingo_horafim': convites_domingo_horafim,
                            'convites_segunda_horainicio': convites_segunda_horainicio,
                            'convites_segunda_horafim': convites_segunda_horafim,
                            'convites_terca_horainicio': convites_terca_horainicio,
                            'convites_terca_horafim': convites_terca_horafim,
                            'convites_quarta_horainicio': convites_quarta_horainicio,
                            'convites_quarta_horafim': convites_quarta_horafim,
                            'convites_quinta_horainicio': convites_quinta_horainicio,
                            'convites_quinta_horafim': convites_quinta_horafim,
                            'convites_sexta_horainicio': convites_sexta_horainicio,
                            'convites_sexta_horafim': convites_sexta_horafim,
                            'convites_sabado_horainicio': convites_sabado_horainicio,
                            'convites_sabado_horafim': convites_sabado_horafim,

                            'convites_domingo': convites_domingo,
                            'convites_segunda': convites_segunda,
                            'convites_terca': convites_terca,
                            'convites_quarta': convites_quarta,
                            'convites_quinta': convites_quinta,
                            'convites_sexta': convites_sexta,
                            'convites_sabado': convites_sabado,
                        };
                        pageurl = '<?= $DiretorioVirtual_Ajax_Usuarios;?>ajax_alterar_usuarios.php';
                        $.ajax({

                            url: pageurl,
                            data: dadosajax,
                            type: 'POST',
                            cache: false,
                            error: function() {},
                            //retorna o resultado da pagina para onde enviamos os dados
                            success: function(result) {
                                //se foi inserido com sucesso
                                if ($.trim(result) == '1') {

                                    alert("Geração de Convites Salvo com Sucesso");
                                }
                                //se foi um erro
                                else {
                                    alert("Ocorreu um erro ao inserir o seu registo!");
                                }

                            }
                        });

                    }
                </script>
                <!-- FIM CONVIES -->
                <?php }?>



                <?php if($_GET['Aba'] == "Restaurante"){ ?>
                <!-- DADOS ACESSO -->
                <div class="card-body" id="restaurante">


                    <h6 class="card-title">Dados de Acesso ao Restaurante</h6>
                    <div class="row">
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label class="control-label">ESCALA RESTAURANTE</label>
                                <select class="form-control" name="idescala" id="idescala">
                                    <option value="" <?php if($idescala == ""){ echo "SELECTED";}?>>Selecione uma Escala</option>
                                    <?php
                                    $SelecionaEscala = Seleciona("Condominio_Escalas", "WHERE IdDivision='$IdDivision' AND excluido IS NULL", "ORDER BY titulo_escala ASC");
                                    while($escala = mysql_fetch_array($SelecionaEscala)){
                                    ?>
                                    <option value="<?= $escala['idescala'];?>" <?php if($idescala == $escala['idescala']){ echo "SELECTED";}?>><?= $escala['titulo_escala'];?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div><!-- Col --> 

                        <div class="col-sm-2">
                            <div class="form-group">
                                <label class="control-label">LIBERADO?</label>
                                <select class="form-control" name="liberado_restaurante" id="liberado_restaurante">
                                    <option value="Nao" <?php if($liberado_restaurante == "Nao"){ echo "SELECTED";}?>>NÃO</option>
                                    <option value="Sim" <?php if($liberado_restaurante == "Sim"){ echo "SELECTED";}?>>SIM</option>
                                </select>
                            </div>
                        </div><!-- Col -->                                            
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label class="control-label">QTD. DIA</label>
                                <input type="number" class="form-control" name="qtde_dia_restaurante" id="qtde_dia_restaurante" value="<?= $qtde_dia_restaurante;?>">
                            </div>
                        </div><!-- Col -->

                        <div class="col-sm-2">
                            <div class="form-group">
                                <label class="control-label">QTD. MÊS</label>
                                <input type="number" class="form-control" name="qtde_mes_restaurante" id="qtde_mes_restaurante" value="<?= $qtde_mes_restaurante;?>">
                            </div>
                        </div><!-- Col -->

                        <div class="col-sm-3">
                            <div class="form-group">
                                <label class="control-label">REFEIÇÃO P/ VISITANTE</label>
                                <select class="form-control" name="gerar_convite_restaurante" id="gerar_convite_restaurante">
                                    <option value="2" <?php if($gerar_convite_restaurante == "2"){ echo "SELECTED";}?>>NÃO</option>
                                    <option value="1" <?php if($gerar_convite_restaurante == "1"){ echo "SELECTED";}?>>SIM</option>
                                </select>
                            </div>
                        </div><!-- Col -->


                    </div><!-- Row --> 

                    <!-- SE ESTIVER VINCULADO HÁ ALGUM GRUPO -->
                    <?php if($idrestaurante_grupo > 0){ ?>
                       <div class="clearfix"></div>
                       <div class="clearfix"></div>
                    <h6 class="card-title">Grupo do Restaurante</h6>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="control-label">Grupo Atual</label>
                                <select class="form-control" name="idrestaurante_grupo" id="idrestaurante_grupo">
                                   <?php
                                   $SelecionaGrupoRestaurante = mysql_query("SELECT idrestaurante_grupo, nome_grupo, quantidade_dia, quantidade_mes FROM Restaurantes_Grupos WHERE IdDivision='$IdDivision' AND excluido IS NULL ORDER BY nome_grupo ASC", $conexao);
                                    while($grupo = mysql_fetch_array($SelecionaGrupoRestaurante)){
                                   ?>
                                    <option value="<?= $grupo['idrestaurante_grupo'];?>" <?php if($grupo['idrestaurante_grupo'] == $idrestaurante_grupo){ echo "SELECTED";}?>><?= $grupo['nome_grupo'] . " | DIA: " . $grupo['quantidade_dia'] . " | MÊS: " . $grupo['quantidade_mes'];?></option>
                                    <?php }?>
                                </select>
                            </div>
                        </div><!-- Col -->                                            
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label class="control-label">QUANTIDADE DIA</label>
                                <input type="number" class="form-control" id="qtde_dia_restaurante_grupo" value="<?= $qtde_dia_restaurante_grupo;?>" readonly>
                            </div>
                        </div><!-- Col -->

                        <div class="col-sm-3">
                            <div class="form-group">
                                <label class="control-label">QUANTIDADE MÊS</label>
                                <input type="number" class="form-control" id="qtde_dia_restaurante_grupo" value="<?= $qtde_dia_restaurante_grupo;?>" readonly>
                            </div>
                        </div><!-- Col -->


                    </div><!-- Row --> 

                <?php }?>
                    <!-- FIM GRUPO RELACIONADO -->

                    <div class="row" style="margin-top: 10px; float: right;">

                        <button type="button" class="btn btn-success" onclick="AlterarDadosRestaurante('<?= $_REQUEST['idpessoa'];?>');" style="float: right;"><i data-feather="save"></i> SALVAR DADOS DO RESTAURANTE</button>

                    </div>                                                 
                </div>
                
                    
                

                <script type="text/javascript">
                    function AlterarDadosRestaurante(idpessoa) {
                        

                        var liberado_restaurante = document.getElementById("liberado_restaurante").value;
                        var qtde_dia_restaurante = document.getElementById("qtde_dia_restaurante").value;
                        var qtde_mes_restaurante = document.getElementById("qtde_mes_restaurante").value;
                        var gerar_convite_restaurante = document.getElementById("gerar_convite_restaurante").value;
                        var idescala = document.getElementById("idescala").value;
                        <?php if($idrestaurante_grupo > 0){ ?>
                            var idrestaurante_grupo = document.getElementById("idrestaurante_grupo").value;
                        <?php }?>    


                        var dadosajax = {

                            'idpessoa': idpessoa,
                            'origem': "Restaurante",                            
                            'liberado_restaurante': liberado_restaurante,                            
                            'qtde_dia_restaurante': qtde_dia_restaurante,
                            'qtde_mes_restaurante': qtde_mes_restaurante,  
                            'gerar_convite_restaurante': gerar_convite_restaurante,  
                            'idescala': idescala,  
                            <?php if($idrestaurante_grupo > 0){ ?>
                            'idrestaurante_grupo': idrestaurante_grupo,  
                            <?php }?>                          
                        };
                        pageurl = '<?= $DiretorioVirtual_Ajax_Usuarios;?>ajax_alterar_usuarios.php';
                        $.ajax({

                            url: pageurl,
                            data: dadosajax,
                            type: 'POST',
                            cache: false,
                            error: function() {},
                            //retorna o resultado da pagina para onde enviamos os dados
                            success: function(result) {
                                //se foi inserido com sucesso
                                if ($.trim(result) == '1') {

                                    alert("Dados de Acesso Salvo com Sucesso!");
                                }
                                //se foi um erro
                                else {
                                    alert("Ocorreu um erro ao inserir o seu registo!");
                                }

                            }
                        });

                    }
                </script>

                <!-- FIM ACESSO -->
                <?php }?>

                <!-- ESCALA RESTAURANTE -->
                <?php if($_GET['Aba'] == "EscalaRestaurante"){ ?>
                <?php                            
                            
if($_GET['AcaoEscala'] == "Adicionar"){
    
$diasemana_inicio_escala   = $_POST['diasemana_inicio_escala'];  
$hora_inicio_escala        = $_POST['hora_inicio_escala'];  
$diasemana_fim_escala      = $_POST['diasemana_fim_escala'];  
$hora_fim_escala           = $_POST['hora_fim_escala'];  
 


$salvar = Salvar("Person_Escala", "idperson, idpessoa, data_cadastro, idusuario_cadastro, origem_cadastro, diasemana_inicio_escala, hora_inicio_escala, diasemana_fim_escala, hora_fim_escala, IdDivision", "'$id', '".$_GET['idpessoa']."', '$datacadastro', '$idusuario_cadastro', 'P2PCLIENTE',  '$diasemana_inicio_escala', '$hora_inicio_escala', '$diasemana_fim_escala', '$hora_fim_escala', '$IdDivision'");


$redireciona = VerificaSql($salvar, "cadastro_usuarios.php?idpessoa=".$idpessoa."&Aba=EscalaRestaurante", "cadastro_usuarios.php?idpessoa=".$idpessoa."&Aba=EscalaRestaurante");

}
    
if($_GET["ExcluirEscala"] == "Sim"){

$deletar = Alterar("Person_Escala", "excluido='Sim'", "idperson_escala", $_GET['idperson_escala']);
    
$redireciona = VerificaSql($deletar, "cadastro_usuarios.php?idpessoa=".$idpessoa."&Aba=EscalaRestaurante", "cadastro_usuarios.php?idpessoa=".$idpessoa."&Aba=EscalaRestaurante");

}     

//regra para pegar opções da escala de perfil
if($idescala > 0){

$ZerarOpcoesEscala = Excluir("Person_Escala", "idescala", $idescala);

$SelecionaOpcoes = Seleciona("Condominio_Escalas_Opcoes", "WHERE idescala='$idescala' AND excluido IS NULL", "ORDER BY diasemana_inicio_escala ASC");
while($escala = mysql_fetch_array($SelecionaOpcoes)){

    

    $diasemana_inicio_escala   = $escala['diasemana_inicio_escala'];  
    $hora_inicio_escala        = $escala['hora_inicio_escala'];  
    $diasemana_fim_escala      = $escala['diasemana_fim_escala'];  
    $hora_fim_escala           = $escala['hora_fim_escala'];  
      
 
    //echo $diasemana_inicio_escala . " - " . $hora_inicio_escala . " - " . $diasemana_fim_escala . " - " .  $hora_fim_escala . "<br>";


$salvar = Salvar("Person_Escala", "idperson, idpessoa, data_cadastro, idusuario_cadastro, origem_cadastro, diasemana_inicio_escala, hora_inicio_escala, diasemana_fim_escala, hora_fim_escala, IdDivision, idescala", "'$id', '".$_GET['idpessoa']."', '$datacadastro', '$idusuario_cadastro', 'P2PCLIENTE',  '$diasemana_inicio_escala', '$hora_inicio_escala', '$diasemana_fim_escala', '$hora_fim_escala', '$IdDivision', '$idescala'");

}

}
                            
?>
                <!-- DADOS ESCALA -->
                <div class="card-body" id="escala">


                    <h6 class="card-title">Dados do(s) Escala(s)</h6>

                    <form method="post" action="cadastro_usuarios.php?idpessoa=<?= $idpessoa;?>&Aba=EscalaRestaurante&AcaoEscala=Adicionar" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label class="control-label">DIA INICIO</label>
                                    <select class="form-control" name="diasemana_inicio_escala">

                                        <option value="DOMINGO">DOMINGO</option>
                                        <option value="SEGUNDA">SEGUNDA</option>
                                        <option value="TERCA">TERÇA</option>
                                        <option value="QUARTA">QUARTA</option>
                                        <option value="QUINTA">QUINTA</option>
                                        <option value="SEXTA">SEXTA</option>
                                        <option value="SABADO">SABADO</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label class="control-label">HORA INICIO</label>
                                    <input type="time" class="form-control" name="hora_inicio_escala" required>
                                </div>
                            </div><!-- Col -->
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label class="control-label">DIA FIM</label>
                                    <select class="form-control" name="diasemana_fim_escala">

                                        <option value="DOMINGO">DOMINGO</option>
                                        <option value="SEGUNDA">SEGUNDA</option>
                                        <option value="TERCA">TERÇA</option>
                                        <option value="QUARTA">QUARTA</option>
                                        <option value="QUINTA">QUINTA</option>
                                        <option value="SEXTA">SEXTA</option>
                                        <option value="SABADO">SABADO</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label class="control-label">HORA INICIO</label>
                                    <input type="time" class="form-control" name="hora_fim_escala" required>
                                </div>
                            </div><!-- Col -->
                        </div><!-- Row -->


                        <button type="submit" class="btn btn-success" id="bt-add-escala"><i data-feather="save"></i> ADICIONAR ESCALA</button>

                    </form>

                </div>

                <div class="card-body">
                    <h6 class="card-title">Escala Adicionada(s)</h6>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>DIA INICIO</th>
                                    <th>HORA INICIO</th>
                                    <th>DIA FIM</th>
                                    <th>HORA FIM</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $SelecionaEscala = Seleciona("Person_Escala", "WHERE idpessoa='$idpessoa' AND excluido IS NULL", "ORDER BY diasemana_inicio_escala ASC");
                            while($escala = mysql_fetch_array($SelecionaEscala)){

                            ?>
                                <tr>
                                    <td><?= $escala['diasemana_inicio_escala'];?></td>
                                    <td><?= $escala['hora_inicio_escala'];?></td>
                                    <td><?= $escala['diasemana_fim_escala'];?></td>
                                    <td><?= $escala['hora_fim_escala'];?></td>
                                    
                                    <td>
                                        <?php if($escala['idescala'] < 1) { ?>
                                        <a href="cadastro_usuarios.php?idpessoa=<?= $idpessoa;?>&idperson_escala=<?= $escala['idperson_escala'];?>&Aba=EscalaRestaurante&ExcluirEscala=Sim">
                                            <button type="button" class="btn btn-danger btn-icon" data-toggle="tooltip" data-placement="top" title="Excluir Escala">
                                                <i data-feather="trash"></i>
                                            </button>
                                        </a>
                                        <?php }else{ ?>
                                            PERFIL ESCALA
                                        <?php }?>
                                    </td>
                                </tr>
                                <?php }?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- FIM ESCALA RESTAURANTE -->
                <?php }?>


                <?php if($_GET['Aba'] == "LogsAlteracoes"){ ?>
                

                <div class="card-body">
                    <h6 class="card-title">Logs Alterações</h6>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>DATA</th>
                                    <th>ALTERADO POR</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $LIKE = '%"idpessoa": "'.$idpessoa.'",%';

                                $SQL_LOGS = "
                                SELECT SL.data_cadastro, P.`name` AS CadastradoPor
                                FROM `Sistema_Logs` AS SL
                                LEFT JOIN Person AS P ON P.idpessoa=SL.cadastrado_por
                                WHERE SL.acao='AlterarUsuario' AND SL.dados_anterior LIKE '$LIKE' 
                                ORDER BY SL.idsistema_log DESC
                                ";
                                $SelecionaLogs = mysql_query($SQL_LOGS, $conexao);
                                while($logs = mysql_fetch_array($SelecionaLogs)){

                                ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i:s', strtotime($logs['data_cadastro']));?></td>
                                    <td><?= $logs['CadastradoPor'];?></td>
                                </tr>
                                <?php }?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- FIM LOGS ALTERAÇÕES -->
                <?php }?>


            </div>
        </div>

    </div>

    </div>



    <!-- EXCLUIR USUARIO -- >


<!-- Modal -->
    <div class="modal fade" id="ExcluirPessoa" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Exclusão de Usuário</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Você Confirma a Exclusão do usuário : <strong> <?= $nome;?> ?</strong><br>
                    Apenas o acesso será removido, históricos e convites permanecerão ativos.

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i data-feather="x-square"></i>Fechar</button>
                    <a href="cadastro_usuarios.php?idpessoa=<?= $idpessoa;?>&ExcluirPessoa=Sim">
                        <button type="button" class="btn btn-danger"><i data-feather="trash"></i>Confirmar Exclusão</button>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!--FIM EXCLUIR USUARIO -->


    <!-- EXCLUIR FOTO -->

    <div class="modal fade" id="ExcluirFoto" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Exclusão de Foto</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Você Confirma a Exclusão da Foto do Usuário : <strong> <?= $nome;?> ?</strong><br>
                    A imagem será removida dos equipamentos sincronizados.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i data-feather="x-square"></i>Fechar</button>
                    <a href="cadastro_usuarios.php?idpessoa=<?= $idpessoa;?>&ExcluirFoto=Sim">
                        <button type="button" class="btn btn-danger"><i data-feather="trash"></i>Confirmar Exclusão</button>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!--FIM EXCLUIR FOTO -->


    <!-- MODELO CARTÃO WIEGAND -->
    
    <!-- Modal -->
    <div class="modal fade" id="ModeloCartao" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
    <div class="modal-content">
    <div class="modal-header">
    <h5 class="modal-title" id="exampleModalLabel">Onde Localizar o Código</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
    <span aria-hidden="true">&times;</span>
    </button>
    </div>
    <div class="modal-body" style="text-align: center;">
    <img src="<?= $DiretorioVirtual_ClientesImg;?>cartao_modelo.jpg" style="width: 96%;">
    <br>
    Ex: 12 345678 (012,34567) 
    </div>
    <div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>    
    </div>
    </div>
    </div>
    </div>

    <!-- FIM MODELO CARTÃO WIEGAND -->


    <!-- MODELO CARTÃO DUPLICADO -->
    
    <!-- Modal -->
    <div class="modal fade" id="ModeloCartaoDuplicado" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog" role="document">
    <div class="modal-content">
    <div class="modal-header">
    <h5 class="modal-title" id="exampleModalLabel">Cartão já esta com outra pessoa</h5>    
    
    </div>
    <div class="modal-body" style="text-align: center;">    
    Esse cartão esta vinculado a: <br>
    <span id="nome_pessoa_exibe" style="font-weight: bold;"></span><br>
    Gostaria de transferir o cartão para <?= $name;?> ?
    </div>
    <div class="modal-footer">
    <button type="button" class="btn btn-danger" onclick="ManterCartao();"> <i data-feather="slash"></i> NÃO TRANSFERIR</button>    
    <button type="button" class="btn btn-success" data-dismiss="modal"><i data-feather="refresh-ccw"></i> TRANSFERIR CARTÃO</button>    
    </div>
    </div>
    </div>
    </div>

    <!-- FIM MODELO CARTÃO WIEGAND -->




<!-- WebCam -->
            
<div class="modal fade" id="WebCam" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
 
      <input type="hidden" name="idpessoa_webcam" id="idpessoa_webcam" value="">
      <input type="hidden" name="foto_webcam" id="foto_webcam" value="">
 
    <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Adicionar Foto no Usuário</h5>
        <button type="button" class="close" aria-label="Close" onclick="FecharModalWebcam();">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">

        <div class="row">

        <div class="col-sm-12">
        <div class="form-group">
        <label class="control-label">NOME USUÁRIO</label>
        <input type="text" class="form-control" id="nome_usuario_webcam" disabled>
        </div>
        </div>

        <div class="col-sm-6">
        <div class="form-group" style="text-align: center;">        
        <video autoplay style="width: 100% !important;"></video>
                
        <button type="button" class="btn btn-dark" onclick="CapturarFoto();"><i data-feather="camera"></i> TIRAR FOTO</button>        
        </div>
        </div>

        <div class="col-sm-6">
        <div class="form-group">        
        <canvas style="width: 100% !important;"></canvas>    
        </div>
        </div>


        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="FecharModalWebcam();"><i data-feather="x-square"></i>FECHAR</button>        
        <button type="button" class="btn btn-success" onclick="SalvarFotoCapturada();" style="display:none;" id="BotaoSalvarFoto"><i data-feather="check"></i>SALVAR FOTO</button>        
      </div>
    </div>
  </div>
   
    
</div>       

<!--FIM MODAL WEBCAM -->



    <?php require_once($DiretorioRaizGlobal.'rodape.php');?>

    </div>
    </div>


    <?php require_once($DiretorioRaizGlobal.'rodape_inclusos.php');?>

    <!-- plugin js for this page -->
    <!-- end plugin js for this page -->
    <!-- custom js for this page -->

    <script src="<?= $DiretorioVirtual;?>assets/vendors/inputmask/jquery.inputmask.min.js"></script>
    <script src="<?= $DiretorioVirtual;?>assets/js/inputmask.js"></script>
    <script src="<?= $DiretorioVirtual;?>assets/vendors/dropify/dist/dropify.min.js"></script>
    <script src="<?= $DiretorioVirtual;?>assets/js/dropify.js"></script>
    <script src="<?= $DiretorioVirtual;?>assets/vendors/select2/select2.min.js"></script>
    <script src="<?= $DiretorioVirtual;?>assets/js/select2.js"></script>
    <script src="<?= $DiretorioVirtual;?>inclusos/canvas-to-blob.min.js"></script>
    <script src="<?= $DiretorioVirtual;?>inclusos/resize.js"></script>

    <!-- end custom js for this page -->
    <script>

                function DescobrirRfid(){

                    
                    var codigow = document.getElementById("crendecial_codigow").value;                
                    var separo_valores = codigow.split(",");

                    var v1 = apenasNumeros(separo_valores[0]);
                    var v2 = apenasNumeros(separo_valores[1]);
                    
                    //alert(v2);

                    serialize_teste(v1, v2, "");

                }

        function apenasNumeros(string) 
        {
            var numsStr = string.replace(/[^0-9]/g,'');
            return parseInt(numsStr);
        }


   function numHex(s)
                  {
                      var a = s.toString(16);
                      if ((a.length % 2) > 0) {
                          a = "0" + a;
                      }
                      return a;
                  }

                  function strHex(s)
                    {
                        var a = "";
                        for (var i=0; i<s.length; i++) {
                            a = a + numHex(s.charCodeAt(i));
                        }

                        return a;
                    }

                    function toHex(s)
                      {
                          var re = new RegExp(/^\s*(\+|-)?((\d+(\.\d+)?)|(\.\d+))\s*$/);

                          if (re.test(s)) {
                              return '#' + strHex( s.toString());
                          }
                          else {
                              return 'A' + strHex(s);
                          }
                      }

 //ToHex
                 function hexIt(number){
                        if (number < 0)
                        {
                          number = 0xFFFFFFFF + number + 1;
                        }

                        return number.toString(16).toUpperCase();
                      }

                      function decimalToHex(d, padding) {
                          var hex = Number(d).toString(16);
                          padding = typeof (padding) === "undefined" || padding === null ? padding = 2 : padding;

                          while (hex.length < padding) {
                              hex = "0" + hex;
                          }

                          return hex;
                      }

                      //Add Leading Zeros to Binary CardNumber
                      function leadZero(number){
                        var zString = "This is a string.";
                        zString = parseInt("0").toString(2);
                        xString = parseInt("0").toString(2);
                        for (var i=0; i<number-1; i++) {
                          zString = zString + xString;
                        }
                          return zString;
                      }
//Serialize Calc
                  function serialize_teste(valor1, valor2, rfid){

                    var binA_teste, binB_teste, binC_teste, binD_teste;
                    /*
                    var facilitycode = document.getElementById("facilitycode").value;
                    var cardnumber = document.getElementById("cardnumber").value*/

                    var facilitycode = valor1;
                    var cardnumber = valor2;


                    if (facilitycode >=0 && facilitycode <256 && cardnumber >0 && cardnumber < 65536)
                        {
                        binA_teste = (parseInt(facilitycode).toString(2));
                        binB_teste = (parseInt(cardnumber).toString(2));
                        cardzero = 16 - binB_teste.length;
                        binC_teste = leadZero(cardzero);
                        binD_teste = binA_teste + binC_teste + binB_teste;
                        var cardNum = parseInt(binD_teste, 2);
                        var rfid = cardNum;


                        //PRIMEIRO COLOCO NO CAMPO TEMPORARIO

                        document.getElementById('temporario').value = rfid; 

                        //AQUI PEGO VALOR DO CAMPO TEMPORARIO

                        var rfid_temporario = document.getElementById("temporario").value;
                        var num_caracteres = document.getElementById("temporario").value.length;

                        if(num_caracteres < 10){

                            if(num_caracteres == 7){
                                var rfid_final = "000" + rfid_temporario;
                                document.getElementById('crendecial_rfid').value = rfid_final; 
                            }


                            if(num_caracteres == 8){
                                var rfid_final = "00" + rfid_temporario;
                                document.getElementById('crendecial_rfid').value = rfid_final; 
                            }

                            if(num_caracteres == 9){
                                var rfid_final = "0" + rfid_temporario;
                                document.getElementById('crendecial_rfid').value = rfid_final; 
                            }


                        }

                        //alert(rfid_final);

                        //document.getElementById("fullcardnumber").innerHTML = cardNum;
                        //document.getElementById("hexcard").innerHTML = cardNum.toString(16);
                        //document.getElementById("bincard").innerHTML = binD;
                        }else{
                            alert('Código Errado do Cartão');
                            document.getElementById('temporario').value = ""; 
                            document.getElementById('crendecial_rfid').value = ""; 
                            document.getElementById('crendecial_codigow').value = ""; 
                        }
                        
                  }


                  
        //RFID
        
        function deserialize(){
                    var binA, binB, binC, binD;
                    var fullcardnumber = document.getElementById("crendecial_rfid").value;
                    if (fullcardnumber >0 && fullcardnumber < 33488896)
                        {
                        binA = parseInt(fullcardnumber).toString(2);
                        var cardNum = parseInt(binA, 2);
                        binC = binA.slice(-16);
                        var facilityLength = binA.length - binC.length;


                        var cardnumber = parseInt(binC,2);
                        if (cardnumber > 32767)
                        {
                          facilityLength = facilityLength - 1 ;
                        }


                        binB = binA.slice(0,facilityLength);

                        //If Zero
                        if (binA.length < 17)
                        {
                          binB = 0;
                        }

                        var codw1 = parseInt(binB,2);
                        var codw2 = parseInt(binC,2);

                        var codw_final = codw1 + "," + codw2;
                        
                        document.getElementById('crendecial_codigow').value = codw_final; 

                        /*
                        document.getElementById("xfacilitycode").innerHTML = parseInt(binB,2);
                        document.getElementById("xcardnumber").innerHTML = parseInt(binC,2);
                        document.getElementById("hexcardnumber").innerHTML = cardNum.toString(16);
                        document.getElementById("bincardnumber").innerHTML = binA;
                        */



                      }
                    else {

                        //alert('DEU ERRADO');
                        /*
                      document.getElementById("xfacilitycode").innerHTML = 0;
                      document.getElementById("xcardnumber").innerHTML = 0;
                      document.getElementById("hexcardnumber").innerHTML = 0;
                      document.getElementById("bincardnumber").innerHTML = 0;*/
                      
                    }
                  }          
                  

        function LimparCampos(){                                                
            $("#cpf").val("");
            $("#cpf").focus();
        }          

        function TestaCPF(strCPF) {
            var Soma;
            var Resto;
            Soma = 0;
          if (strCPF == "00000000000") return false;
          if (strCPF == "11111111111") return false;
          if (strCPF == "22222222222") return false;
          if (strCPF == "33333333333") return false;
          if (strCPF == "44444444444") return false;
          if (strCPF == "55555555555") return false;
          if (strCPF == "66666666666") return false;
          if (strCPF == "77777777777") return false;
          if (strCPF == "88888888888") return false;
          if (strCPF == "99999999999") return false;

          for (i=1; i<=9; i++) Soma = Soma + parseInt(strCPF.substring(i-1, i)) * (11 - i);
          Resto = (Soma * 10) % 11;

            if ((Resto == 10) || (Resto == 11))  Resto = 0;
            if (Resto != parseInt(strCPF.substring(9, 10)) ) return false;

          Soma = 0;
            for (i = 1; i <= 10; i++) Soma = Soma + parseInt(strCPF.substring(i-1, i)) * (12 - i);
            Resto = (Soma * 10) % 11;

            if ((Resto == 10) || (Resto == 11))  Resto = 0;
            if (Resto != parseInt(strCPF.substring(10, 11) ) ) return false;
            return true;
        }

        function CpfBloqueado() {

            document.getElementById("bt-salvar").style.display = "none";
            
            var cpf = document.getElementById('cpf').value;            
            
            var cpf_verifica1 = cpf.replace("-", "");
            var cpf_verifica2 = cpf_verifica1.replace(".", "");
            var cpf_verifica_final = cpf_verifica2.replace(".", "");
            
            
            
            if(cpf.length == 14){
                
            //alert(cpf_verifica_final);    
            //alert(TestaCPF(cpf_verifica_final));    
            if(TestaCPF(cpf_verifica_final)){    
            
            $.ajax({
            url: "<?= $DiretorioVirtual_Ajax_Convites;?>verifica_cpf_bloqueado.php?cpf=" + cpf + "&origem=Pessoas",
            method: "post",
                success: function(data) {                                                                
                    
                
                    if(data == "Liberado"){

                        document.getElementById("bt-salvar").style.display = "initial";
                        
                        
                        //AtivoCampos();

                        //alert('TO AQUI LIBERADO');
                        
                        
                        
                    }

                    if(data == "Bloqueado"){                       
                        
                        $(function() {

                        Swal.fire({
                        //position: 'top-end',
                        icon: 'error',
                        title: 'CPF Bloqueado para acesso ao Condominio, entre em contato com seu ADMINISTRADOR',
                        showConfirmButton: false,
                        timer: 3500
                        }).then(function() {

                        //window.location.href = "login.php";


                        })
                        });
                        
                        //DesativaCampos();
                        LimparCampos();
                        document.getElementById("bt-salvar").style.display = "initial";
                    }

                    if(data == "Duplicado"){                       
                        
                        $(function() {

                        Swal.fire({
                        //position: 'top-end',
                        icon: 'error',
                        title: 'CPF já Cadastrado nessa empresa, verifique na lista de usuários ou entre em contato com seu ADMINISTRADOR',
                        showConfirmButton: false,
                        timer: 3500
                        }).then(function() {

                        //window.location.href = "login.php";


                        })
                        });
                        
                        //DesativaCampos();
                        LimparCampos();
                        document.getElementById("bt-salvar").style.display = "initial";
                    }


                }
            });
                
            }else{
                
                $(function() {

                Swal.fire({
                //position: 'top-end',
                icon: 'error',
                title: 'CPF Inválido, informe um CPF correto para criar um usuário.',
                showConfirmButton: false,
                timer: 4000
                }).then(function() {

                //window.location.href = "login.php";
                LimparCampos();
                $("#cpf").focus();
                

                })
                });
                
                //DesativaCampos();
                LimparCampos();

                document.getElementById("bt-salvar").style.display = "initial";
                
            }
                
            }
        }   
                  
function PlacaBloqueado() {

            document.getElementById("bt-add-veiculo").style.display = "none";
                        
            var placa = document.getElementById('placa').value;
            
            if(placa.length > 4){
            
            $.ajax({
            url: "<?= $DiretorioVirtual_Ajax_Convites;?>verifica_placa_bloqueado.php?placa=" + placa + "&origem=Pessoas",
            method: "post",
                success: function(data) {                                                                                
                
                    if(data == "Liberado"){                        
                        
                        //AtivoCampos();

                        //alert('TO AQUI LIBERADO');  
                        document.getElementById("bt-add-veiculo").style.display = "initial";                                              
                        
                    }else{                        
                        
                        $(function() {
                        Swal.fire({
                        //position: 'top-end',
                        icon: 'error',
                        title: 'PLACA Bloqueada para acesso ao Condominio, entre em contato com seu ADMINISTRADOR',
                        showConfirmButton: false,
                        timer: 4000
                        }).then(function() {

                        //window.location.href = "login.php";

                        $("#placa").val("");  

                        document.getElementById("bt-add-veiculo").style.display = "initial"; 

                        })
                        });
                        
                    }
                }
            });
                
        }   

}


function VericaCodW(campo, origem) {
                        
            var cartao = document.getElementById(campo).value;
            
            if(cartao.length > 7){

            $(function() {

            Swal.fire({
            //position: 'top-end',


            /*imageUrl: "<?= $DiretorioVirtual_ClientesImg;?>icone_carregando.gif",*/
            icon: 'info',
            title: 'Verificando Base de Credenciais',
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false

            /*timer: 4500*/
            }).then(function() {

            //window.location.href = "login.php";


            })
            });
            
            $.ajax({
            url: "<?= $DiretorioVirtual_Ajax_Usuarios;?>verifica_codigo_w.php?cartao=" + cartao + "&origem=" + origem,
            method: "post",
                success: function(data) {                 

                    if(data != "Liberado" && data != "Bloqueado"){                        
                        
                        $("#nome_pessoa_exibe").append(data);  
                        $('#ModeloCartaoDuplicado').modal('show');
                                                                                            

                        
                    }                                                               
                
                    if(data == "Liberado"){                        
                        
                        //AtivoCampos();

                        //alert('TO AQUI LIBERADO');                                                                        

                        
                    }

                    if(data == "Bloqueado"){                        
                        
                        $(function() {
                        Swal.fire({
                        //position: 'top-end',
                        icon: 'error',
                        title: 'CARTÃO já existe na base de dados, verifique com o Administrador',
                        showConfirmButton: false,
                        timer: 4000
                        }).then(function() {

                        //window.location.href = "login.php";

                        $("#crendecial_codigow").val("");   
                        $("#crendecial_rfid").val("");   
                        

                        })
                        });                        
                        $("#crendecial_codigow").focus();
                    }


                    swal.close();

                }
            });
                
        }   

}

    function ManterCartao() {

            $('#ModeloCartaoDuplicado').modal('hide');
            $("#crendecial_codigow").val("");   
            $("#crendecial_rfid").val("");   
            $("#crendecial_codigow").focus();
            
        }

    function OnCheckboxValue(input){

    var checkbox = document.getElementById(input);

    if(checkbox.checked) {                        
    document.getElementById(input).value='Sim'; 
    }else{
    document.getElementById(input).value=''; 
    }     

    }

    $(document).ready(function() {
  $(window).keydown(function(event){
    if(event.keyCode == 13) {
      event.preventDefault();
      return false;
    }
  });
});
    
</script>


<script type="text/javascript">

        // Iniciando biblioteca
        var resize = new window.resize();
        resize.init();

        // Declarando variáveis
        var imagens;
        var imagem_atual;

        // Quando carregado a página
        $(function ($) {

            // Quando selecionado as imagens
            $('#myDropify').on('change', function () {
                enviar();
            });

        });

        /*
         Envia os arquivos selecionados
         */
        function enviar()
        {
            // Verificando se o navegador tem suporte aos recursos para redimensionamento
            if (!window.File || !window.FileReader || !window.FileList || !window.Blob) {
                alert('O navegador não suporta os recursos utilizados pelo aplicativo');
                return;
            }

            // Alocando imagens selecionadas
            imagens = $('#myDropify')[0].files;

            // Se selecionado pelo menos uma imagem
            if (imagens.length > 0)
            {
                // Definindo progresso de carregamento
                $('#progresso').attr('aria-valuenow', 0).css('width', '0%');

                // Escondendo campo de imagem
                $('#myDropify').hide();

                // Iniciando redimensionamento
                imagem_atual = 0;
                redimensionar();
            }
        }

        /*
         Redimensiona uma imagem e passa para a próxima recursivamente
         */
        function redimensionar()
        {
            // Se redimensionado todas as imagens
            if (imagem_atual > imagens.length)
            {
                // Definindo progresso de finalizado
                $('#progresso').html('Foto enviada com sucesso');

                // Limpando imagens
                limpar();

                //ATUALIZO APÓS LEITURA

                setTimeout(function() {
                  window.location.reload(1);
                }, 3000); // 3 minutos

                // Exibindo campo de imagem
                $('#myDropify').show();

                // Finalizando
                return;
            }

            // Se não for um arquivo válido
            if ((typeof imagens[imagem_atual] !== 'object') || (imagens[imagem_atual] == null))
            {
                // Passa para a próxima imagem
                imagem_atual++;
                redimensionar();
                return;
            }

            // Redimensionando
            resize.photo(imagens[imagem_atual], 500, 'dataURL', function (imagem) {

                // Salvando imagem no servidor
                $.post('<?= $DiretorioVirtual_Ajax_Usuarios;?>salvar_foto.php?idpessoa=<?= $idpessoa;?>', {imagem: imagem}, function() {

                    // Definindo porcentagem
                    var porcentagem = (imagem_atual + 1) / imagens.length * 100;

                    // Atualizando barra de progresso
                    $('#progresso').text(Math.round(porcentagem) + '%').attr('aria-valuenow', porcentagem).css('width', porcentagem + '%');

                    // Aplica delay de 1 segundo
                    // Apenas para evitar sobrecarga de requisições
                    // e ficar visualmente melhor o progresso
                    setTimeout(function () {
                        // Passa para a próxima imagem
                        imagem_atual++;
                        redimensionar();
                    }, 1000);

                });

            });
        }

        /*
         Limpa os arquivos selecionados
         */
        function limpar()
        {
            var input = $("#myDropify");
            input.replaceWith(input.val('').clone(true));
        }
    </script>




<script type="text/javascript">
    //PARA WEBCAM

function stopStreamedVideo(videoElem) {
  const stream = videoElem.srcObject;
  const tracks = stream.getTracks();

  tracks.forEach((track) => {
    track.stop();
  });

  videoElem.srcObject = null;
}

//MODAL FOTO WEBCAM
function FotoWebCam(idpessoa, nome){
    
        //CARREGAR DADOS AJAX

        $('#idpessoa_webcam').val('');
        $('#nome_usuario_webcam').empty();

        $('#idpessoa_webcam').val(idpessoa);  
        $('#nome_usuario_webcam').val(nome);  


        var video = document.querySelector('video');

        navigator.mediaDevices.getUserMedia({video:true})
        .then(stream => {
        video.srcObject = stream;
        video.play();
        })
        .catch(error => {
        console.log(error);
        })

        //stopStreamedVideo(video); AQUI CASO EU QUEIRA PARAR O VIDEO
         


    //ABRIR MODAL
    $('#WebCam').modal('show');



}


//SALVO IMAGEM PARA O VISITANTE

function SalvarFotoCapturada() {    


    //AQUI FECHO MODAL

    $('#WebCam').modal('hide');

    //AQUI PARO VIDEO

    var video = document.querySelector('video');

        navigator.mediaDevices.getUserMedia({video:true})
        .then(stream => {
        video.srcObject = stream;
        video.play();
        })
        .catch(error => {
        console.log(error);
        })

        stopStreamedVideo(video); 

    $(function() {

        Swal.fire({
        //position: 'top-end',
        
        
        imageUrl: "<?= $DiretorioVirtual_ClientesImg;?>icone_carregando.gif",
        /*icon: 'info',*/
        title: 'Aguarde Capturando a Foto',
        showConfirmButton: false,
        allowOutsideClick: false,
        allowEscapeKey: false

        /*timer: 4500*/
        }).then(function() {

        //window.location.href = "login.php";


        })
        });
    
    
    var idpessoa = $("#idpessoa_webcam").val();
    var foto_webcam = $("#foto_webcam").val();

    

    //ENVIO POST PRA AJAX 
    var formData = {
        idpessoa: idpessoa,                
        foto_webcam: foto_webcam,                
        IdDivision: <?= $IdDivision;?>,
        cadastrado_por: <?= $_SESSION['usuarioID'];?>

    };

    $.ajax({
        type: "POST",
        url: "<?= $DiretorioVirtual_Ajax_Usuarios;?>salvar_foto_webcam.php",
        data: formData,
        dataType: "json",
        encode: true,
    }).done(function(data) {

        if (!data.success) {
            
            //AQUI COLOCO UM ALERTA QUE CORREU TUDO OK
            //DOU UM REFRESH PARA ATUALIZAR A PAGINA E CARREGAR A FOTO
            

            $(function() {

            Swal.fire({
            //position: 'top-end',
            icon: 'success',
            title: 'Imagem capturada com Sucesso.',
            showConfirmButton: false,
            timer: 2500
            }).then(function() {

            //window.location.reload();
            window.location.href = "cadastro_usuarios.php?idpessoa=<?= $idpessoa;?>&Sincronizar=Pessoa";


            })
            });        
        }

    });            



};

//SALVO IMAGEM PARA O VISITANTE

function FecharModalWebcam(){

    window.location.reload();

}

//AQUI A AÇÃO PARA CAPTURA E SALVAR A IMAGEM
function CapturarFoto(){

        $('#foto_webcam').val('');
        

        var video = document.querySelector('video');



        var canvas = document.querySelector('canvas');
        canvas.height = video.videoHeight;
        canvas.width = video.videoWidth;
        var context = canvas.getContext('2d');
        context.drawImage(video, 0, 0);
        var link = document.createElement('a');
        link.download = 'foto.png';
        link.href = canvas.toDataURL();
        link.textContent = 'Clique para baixar a imagem';
        document.body.appendChild(link);

         $('#foto_webcam').val(link);  

         //AQUI EXIBO O BOTÃO SALVAR DEPOIS QUE JA TENHO A FOTO
         document.getElementById("BotaoSalvarFoto").style.display = "block";


        };   


        $(function() { //onload aqui
    $('#nacionalidade').on('change', function() {
    $('.div-sel').hide();
    let idSelecionado = $(this).val(); //construir o id
    if (idSelecionado != "") $("#" + idSelecionado).show(); //mostrar o elemento

    if(idSelecionado == "brasileira"){
        //$("#celular_brasileira").show();
        //$("#celular_estrangeira").hide();
        $("#cpf").prop('required',true);
    }else{
        //$("#celular_estrangeira").show();
        //$("#celular_brasileira").hide();
        $("#cpf").prop('required',false);
        //$("#celular").prop('required',false);


    }

    });

    $('#nacionalidade').trigger("change"); //aplicar a lógica do change
    });


</script>


</body>

</html>

<?php

if($_GET['Sincronizar'] == "Pessoa"){



//VERIFICO SE TENHO RESIDENTE NA ROTA E SINCRONIZADO NA CONTROLADORA SE NÃO PRECISO ENVIAR

//LOCAIS SINCRONIZADO COM RECNO
/*
$SelecionaEquipamentosSincronizados = mysql_query("SELECT idpessoa FROM Equipamentos_IntelBras_Recno WHERE idpessoa='$idpessoa'", $conexao);
$TotalSincronizados = mysql_num_rows($SelecionaEquipamentosSincronizados);*/
/*
//echo "<BR><BR><BR><BR>AAAA";
  $sql_equipamentos = "SELECT RCL.idlocal_faixa, CLF.idequipamento, CP.ip_equipamento, CP.porta_equipamento, CP.login_equipamento, CP. senha_equipamento, CP.ip_servidor_equipamento
  FROM Rotas_Condominios_Locais AS RCL
  LEFT JOIN Condominio_Locais_Faixas AS CLF ON RCL.idlocal_faixa=CLF.idlocal_faixa
  LEFT JOIN Condominio_Equipamentos AS CP ON CLF.idequipamento=CP.idequipamento
  WHERE RCL.idrota='$idrota' AND RCL.excluido IS NULL AND CLF.idequipamento IS NOT NULL
  GROUP BY RCL.idlocal_faixa";

  $SelecionaEquipamentos = mysql_query($sql_equipamentos, $conexao);
  $TotalEquipamentos = mysql_num_rows($SelecionaEquipamentos);

 // echo $TotalEquipamentos . " - "  . $TotalSincronizados . "<br>";

  if($TotalSincronizados <> $TotalEquipamentos){*/
    //PRECISO ENVIAR PARA FILA
    //echo "<br>";
    //echo "PRECISO ENVIAR PARA FILA";

    $url_processamento = "https://p2pconecta.com.br/api/intelbras/fila_processamento_residentes.php?idpessoa=" . $idpessoa;
    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => $url_processamento,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    //echo $response;

 // }


?>

<script type="text/javascript">
    $(function() {

    Swal.fire({
    //position: 'top-end',
    icon: 'success',
    title: 'Sincronizado com Sucesso',
    showConfirmButton: false,
    timer: 9000
    }).then(function() {

    window.location.href = "cadastro_usuarios.php?idpessoa=<?= $idpessoa;?>";


    })
    });
</script>

<?php
    
}
?>
