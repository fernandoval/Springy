# Configurações da classe de tratamento de URI/URL

O arquivo `\conf\uri.default.conf.php` e as variantes para ambientes
`\conf\${enviroment}\uri.conf.php` armazenam as entradas de configuração para
tratamento de URL.

As entradas de configuração dos arquivos *uri*, são utilizadas pela classe URI,
sendo que as entradas previamente definidas não podem ser omitidas sob risco de
dano à aplicação.

Você poderá adicionar entradas personalizadas de sua aplicação.

## Entradas de configuração:

-   **'routes'** - Array contendo rotas de controladoras. Útil para utilizar
mais de uma URL apontando para mesma controladora. Este modelo está
descontinuado na versão 4.5.0 do framework. Use o novo sistema de rotas;
-   **'redirects'** - Array de redirecionamentos. Útil para configurar
redirecionamentos a acessos que possam causar erro de página não encontrada.
Este modelo está descontinuado na versão 4.5.0 do framework. Use o novo sistema
de rotas;
-   **'prevalidate_controller'** - Validação prévia de formatação de URI para
determinadas controladoras. Este modelo está descontinuado na versão 4.5.0 do
framework. Use o novo sistema de rotas;
-   **'system_root'** - URI da página inicial;
-   **'common_urls'** - Essa entrada é um array de URLs comuns da aplicação que
irão gerar variáveis de template.;
-   **'redirect_last_slash'** - Valor boleano que determina se a aplicação deve
redirecionar requisições a URIs terminadas em barra (/) para URI semelhante sem
a barra no final. Útil para evitar conteúdo duplicado em ferramentas SEO;
-   **'force_slash_on_index'** - Força o uso de barra (/) ao final da URL para o
primeiro segmento (index). Se a URL acessada for da página principal (index) e
não houver a barra ao final, faz o redirecionamento para a URL com / ao final.
Esse parâmetro invalida *'redirect_last_slash'* para a página principal.
-   **'ignored_segments'** - Quantidade de segmentos iniciais que devem ser
ignorados na descoberta da controladora. Útil para sites onde alguns dos
segmentos iniciais servem para algum parâmetro a ser passado para a aplicação,
como por exemplo para determinar idioma, localização ou setor.
Por exemplo: http://www.seusite.com/pt-br/pagina
-   **'host_controller_path'** - Array de hosts que possuem nível próprio de
controladoras;
-   **'register_method_set_common_urls'** - Endereço para função responsável por
inicializar variáveis de template com URLs para o site;
-   **'dynamic'** - Host do conteúdo dinâmico do site.
Ex.: 'http://www.seusite.com'
-   **'static'** - Host do conteúdo estático do site.
Ex.: 'http://cdn.seusite.com'
-   **'secure'** - Host seguro do site.
Ex.: 'https://www.seusite.com'

### Entrada 'common_urls':

Cada entrada do array contém uma variável de template, onde o índice é uma
string contendo o nome da variável e o valor é um array podendo conter de um a
todos os parâmetros necessários para construção da URL, conforme a ordem do
método buildURL da classe URI.

Logo: cada índice do array conterá a seguinte informação, respectivamente:

-   índice 0 (zero) => array de segmentos da URL;
-   índice 1 => array da query string;
-   índice 2 => boleano para o parâmetro forceRewrite;
-   índice 3 => entrada de configuração host;
-   índice 4 => booleano para o parânetro include_ignores_segments.

### Entrada 'register_method_set_common_urls';

-   **'class'** - String com o nome da classe;
-   **'method'** - String com o nome da função;
-   **'static'** - Buleano informando se é chamada estática.

#### Exemplos:

No exemplo a seguir, a aplicação irá executar `Url1::setCommon()`:

```php
$conf = [
    'register_method_set_common_urls' => [
        'class' => 'Url1',
        'method' => 'setCommon',
        'static' => true
       ],
];
```

No exemplo a seguir, a aplicação irá executar `(new Urls())->setCommon()`:

```php
$conf = [
    'register_method_set_common_urls' => [
        'class' => 'Urls',
        'method' => 'setCommon',
        'static' => false
       ],
];
```

No exemplo a seguir, a aplicação irá executar `new Urls()`:

```php
$conf = [
    'register_method_set_common_urls' => [
        'class' => 'Urls',
        'static' => false
    ],
];
```
