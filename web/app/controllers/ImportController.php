<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\ImportCsv;
use Elabftw\Services\ImportZip;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Import a zip or a csv
 */
require_once \dirname(__DIR__) . '/init.inc.php';

$Response = new RedirectResponse('../../admin.php');

try {
    if (!$App->Session->get('is_admin')) {
        throw new IllegalActionException('Non admin user tried to access import controller.');
    }

    // CSRF
    $App->Csrf->validate();

    // it might take some time and we don't want to be cut in the middle, so set time_limit to ∞
    \set_time_limit(0);

    if ($Request->request->get('type') === 'csv') {
        $Import = new ImportCsv($App->Users, $App->Request);
    } elseif ($Request->request->get('type') === 'zip') {
        $Import = new ImportZip($App->Users, $App->Request);
    } else {
        throw new IllegalActionException('Invalid argument');
    }
    $Import->import();

    $msg = $Import->inserted . ' ' .
        ngettext('item imported successfully.', 'items imported successfully.', $Import->inserted);
    $App->Session->getFlashBag()->add('ok', $msg);
} catch (ImproperActionException $e) {
    // show message to user
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $App->Session->getFlashBag()->add('ko', Tools::error(true));
} catch (DatabaseErrorException | FilesystemErrorException $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $App->Session->getFlashBag()->add('ko', Tools::error());
} finally {
    $Response->send();
}
