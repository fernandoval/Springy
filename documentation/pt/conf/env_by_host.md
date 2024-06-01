# Arquivo de configuração de definição de ambiente pelo host

O arquivo **env_by_host.php** na pasta `/conf` é usado pela **Kernel** para
definir um ambiente diferente do padrão conforme o host acessado.

Essa configuração retorna um array contendo um conjunto `chave => valor`, onde a
chave representa um apelido para o ambiente e valor define o ambiente. A chave
pode ser uma expressão regular. Esta entrada de configuração fará o sistema
substituir a configuração do ambiente se o host do site for encontrado nela.
