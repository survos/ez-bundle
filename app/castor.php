<?php



use Castor\Attribute\AsTask;

use function Castor\{io, run, fs, variable, finder, http_request, import};

try {
    import('.castor/vendor/tacman/castor-tools/castor.php');
//    import('composer://castor-php/php-qa');
} catch (\Throwable $th) {
    io()->error("Run\n\ncastor bootstrap\n\n and ignore this warning the first time");
//    io()->error("Run\n\ncastor composer req tacman/castor-tools");
}

#[AsTask('bootstrap', description: 'bootstrap castor tools')]
function bootstrap(): void
{
    io()->warning($cmd = 'castor composer req tacman/castor-tools');
    if (io()->confirm("Run it now?", true)) {
        run($cmd);
        io()->error($cmd);
    }
}


function getBundleName(): string
{
    return variable('bundle_name', 'survos/ez-bundle');
}

function getSkeletonPath(): string
{
    $bundleName = getBundleName();
    return sprintf('vendor/%s/app', $bundleName);
}

#[AsTask('cwd', description: 'display the current working directory')]
function ez_app_cwd(): void
{
    io()->writeln(sprintf('<info>%s</info>', \Castor\context()->workingDirectory));
}

#[AsTask('setup', description: 'Setup bundles and directories, start server')]
function setup(): void
{
    io()->title('Installing required bundles');
    run('composer req endroid/qr-code-bundle survos/ez-bundle:dev-main');
    run('composer req easycorp/easyadmin-bundle');
    run('composer req symfony/ux-icons');

    io()->title('Creating directories');
    $dirs = ['src/Command', 'src/Entity', 'src/Repository', 'templates'];
    foreach ($dirs as $dir) {
        if (!fs()->exists($dir)) {
            fs()->mkdir($dir);
            io()->success("Created {$dir}");
        } else {
            io()->note("{$dir} already exists");
        }
    }

    io()->title('Starting Symfony server');
    run('symfony server:start -d');
}

#[AsTask('ez', description: 'Setup easyadmin as landing page -- readonly')]
function easyadmin(): void
{
    io()->title('configure easyadmin');
    $root = 'src/Controller/Admin';
    fs()->mkdir($root, 0755);
    $skeletonPath = __DIR__ . '/' . getSkeletonPath() . '/src/Controller/Admin';

    foreach (finder()->in($skeletonPath)->files() as $file) {
        fs()->copy($file->getRealPath(), $targetFile = 'src/Controller/Admin/' . $file->getBasename());
        dump($file->getRealPath(), $targetFile);
        io()->write(file_get_contents($targetFile));
    }
    run('bin/console cache:clear');
    open();
}

#[AsTask('database', description: 'Configure and initialize database')]
function database(): void
{
    io()->title('Configuring database');

    if (!fs()->exists('.env.local')) {
        $dbUrl = 'DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"';
        fs()->appendToFile('.env.local', $dbUrl . PHP_EOL);
        io()->success('Created .env.local with SQLite configuration');
    } else {
        io()->note('.env.local already exists');
    }

    io()->title('Creating database schema');
    run('bin/console doctrine:schema:update --force --dump-sql');
}

#[AsTask('copy-files', description: 'Copy demo files from bundle to app')]
function copy_files(): void
{
    $base = getSkeletonPath();

    if (!fs()->exists($base)) {
        io()->error("Skeleton path not found: {$base}");
        io()->note('Make sure the bundle is installed via composer');
        return;
    }

    $files = [
        'src/Entity/Product.php',
        'src/Repository/ProductRepository.php',
        'src/Command/LoadCommand.php', // Fixed typo
        'templates/product.html.twig', // we already have base installed!
    ];

    io()->title('Copying skeleton files');

    foreach ($files as $file) {
        $source = $base . '/' . $file;
        $target = $file;

        if (!fs()->exists($source)) {
            io()->warning("Source file not found: {$source}");
            continue;
        }

        // Create parent directory if needed
        $targetDir = dirname($target);
        if (!fs()->exists($targetDir)) {
            fs()->mkdir($targetDir);
        }

        fs()->copy($source, $target);
        io()->success("Copied {$file}");
    }

    easyadmin(); //
}

#[AsTask('app:load', description: 'Import demo data')]
function app_load(): void
{
    io()->title('Importing product data');
    run('bin/console app:load'); // Match your actual command name
}

#[AsTask('open', description: 'Start web server and open in browser')]
function open(
    #[\Castor\Attribute\AsArgument] string $path='/product'
): void
{
    run('symfony open:local --path=' . $path); // Adjust path as needed
}

#[AsTask('build', description: 'Complete demo setup (all steps)')]
function build(): void
{
    io()->section('Building complete demo application');

    setup();
    copy_files(); // entities, app:load
    easyadmin();
    database();
    app_load();
    open();

    io()->success('Demo application built successfully!');
    io()->note('Visit the opened browser to see the demo');
}

#[AsTask('clean', description: 'Remove generated files and reset')]
function clean(): void
{
    if (!io()->confirm('This will remove generated files. Continue?', false)) {
        return;
    }

    io()->title('Cleaning up demo files');

    $filesToRemove = [
        'src/Entity/Product.php',
        'src/Repository/ProductRepository.php',
        'src/Command/LoadCommand.php',
        'templates/products.html.twig',
        'var/data.db',
    ];

    foreach ($filesToRemove as $file) {
        if (fs()->exists($file)) {
            fs()->remove($file);
            io()->success("Removed {$file}");
        }
    }
}
