<?php

use Concrete\Core\Attribute\CustomNoValueTextAttributeInterface;
use Concrete\Core\File\Set\Set as FileSet;
use Concrete\Core\User\User;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var Concrete\Core\Application\Service\Dashboard $dashboard
 * @var Concrete\Core\Form\Service\Form $form
 * @var Concrete\Core\Html\Service\Html $html
 * @var Concrete\Core\Application\Service\UserInterface $interface
 * @var Concrete\Core\Validation\CSRF\Token $token
 * @var Concrete\Controller\SinglePage\Dashboard\Files\Details $controller
 * @var Concrete\Core\Localization\Service\Date $date
 * @var Concrete\Core\Utility\Service\Number $number
 * @var Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface $resolverManager
 * @var Concrete\Core\Entity\File\Version $fileVersion
 * @var Concrete\Core\Permission\Checker $filePermissions
 * @var string $thumbnail
 * @var Concrete\Core\Entity\Attribute\Key\FileKey[] $attributeKeys
 * @var Concrete\Core\Entity\Statistics\UsageTracker\FileUsageRecord[] $usageRecords
 * @var Concrete\Core\Entity\File\DownloadStatistics[][] $recentDownloads
 */

$file = $fileVersion->getFile();
?>
<section>
    <h3><?= t('Preview') ?></h3>
    <div class="ccm-file-manager-details-preview">
        <div class="ccm-file-manager-details-preview-thumbnail">
            <?= $thumbnail ?>
        </div>
        <div class="ccm-file-manager-details-preview-actions">
            <?php
            if ($filePermissions->canEditFileContents()) {
                ?>
                <div class="mb-4">
                    <a
                        class="btn btn-secondary dialog-launch"
                        dialog-title="<?= t('Swap') ?>"
                        dialog-width="620" dialog-height="400"
                        href="<?= h($resolverManager->resolve(['/ccm/system/dialogs/file/replace?fID=' . $file->getFileID()])) ?>"
                    ><?= t('Swap') ?></a>
                    <div class="text-muted"><i><?= t('Upload a new file to be used everywhere this current file is referenced.') ?></i></div>
                </div>
                <?php
            }
            if ($filePermissions->canEditFileContents()) {
                ?>
                <div class="mb-4">
                    <form method="POST" action="<?= h($controller->action('rescan', $file->getFileID())) ?>">
                        <?php $token->output("ccm-filedetails-rescan-{$file->getFileID()}") ?>
                        <button type="submit" class="btn btn-secondary"><?= t('Rescan')?></button>
                    </form>
                    <div class="text-muted"><i><?= t('Automatically regenerate thumbnails for all sizes of this image.') ?></i></div>
                </div>
                <?php
            }
            if ($fileVersion->canEdit() && $filePermissions->canEditFileContents()) {
                ?>
                <div>
                    <a
                        class="btn btn-secondary dialog-launch"
                        dialog-title="<?= t('Edit') ?>"
                        dialog-width="90%" dialog-height="75%"
                        href="<?= REL_DIR_FILES_TOOLS_REQUIRED . '/files/edit?fID=' . $file->getFileID() ?>"
                    ><?= t('Edit')?></a>
                    <div class="text-muted"><i><?= t('Adjust cropping and scale of this image and all its thumbnails.') ?></i></div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</section>

<hr class="mt-5 mb-4" />

<section>
    <a
        class="btn btn-secondary float-right dialog-launch"
        dialog-title="<?= t('Attributes') ?>"
        dialog-width="680" dialog-height="450"
        href="<?= h($resolverManager->resolve(['/ccm/system/dialogs/file/properties?fID=' . $file->getFileID()])) ?>"
    ><?= t('Edit') ?></a>
    <h3><?= t('Attributes') ?></h3>
    <dl class="ccm-file-manager-details-attributes">
        <dt><?= t('Title') ?></dt>
        <dd><div><?= (string) $fileVersion->getTitle() === '' ? '<i>' . t('No title') . '</i>' : h($fileVersion->getTitle()) ?></div></dd>
        <dt><?= t('Description') ?></dt>
        <dd><div><?= (string) $fileVersion->getDescription() === '' ? '<i>' . t('No description') . '</i>' : nl2br(h($fileVersion->getDescription())) ?></div></dd>
        <dt><?= t('Tags') ?></dt>
        <dd>
            <?php
            $tags = preg_split('/\s*\n\s*/', (string) $fileVersion->getTags(), -1, PREG_SPLIT_NO_EMPTY);
            if ($tags === []) {
                ?>
                <i><?= t('No tags') ?></i>
                <?php
            } else {
                ?>
                <sapn><?= implode(', ', $tags) ?></span>
                <?php
            }
            ?>
            <div class="text-muted"><i><?= t('Search for files with these tags using the advanced search link in the file manager.') ?></i></div>
        </dd>
        <dt><?= t('Sets') ?></dt>
        <dd>
            <?php
            $fileSets = $file->getFileSets();
            if ($fileSets === []) {
                ?>
                <i><?= t('No file set') ?></i>
                <?php
            } else {
                $fileSetNames = array_map(
                    function (FileSet $fileSet) {
                        return $fileSet->getFileSetDisplayName();
                    },
                    $fileSets
                );
                ?>
                <sapn><?= implode(', ', $fileSetNames) ?></sapn>
                <?php
            }
            ?>
            <div class="text-muted"><i><?= t('You can add this file to many sets. Lots of image sliders/galleries use sets to determine what to display.') ?></i></div>
        </dd>
        <?php
        foreach ($attributeKeys as $attributeKey) {
            ?>
            <dt><?= $attributeKey->getAttributeKeyDisplayName() ?></dt>
            <dd><div>
                <?php
                $attributeValue = $fileVersion->getAttributeValueObject($attributeKey);
                if ($attributeValue === null) {
                    $noValueDisplayHtml = '<i>' . t('None') . '</i>';
                    if (method_exists($attributeKey, 'getController')) {
                        $attributeController = $attributeKey->getController();
                        if ($attributeController instanceof CustomNoValueTextAttributeInterface) {
                            $noValueDisplayHtml = (string) $attributeController->getNoneTextDisplayValue();
                        }
                    }
                    echo $noValueDisplayHtml;
                } else {
                    echo (string) $attributeValue;
                }
                ?>
            </div></dd>
            <?php
        }
        ?>
    </dl>
</section>

<hr class="mt-5 mb-4" />

<section>
    <h3><?= t('Statistics') ?></h3>
    <dl class="ccm-file-manager-details-statistics">
        <dt><?= t('Date Added') ?></dt>
        <dd>
            <?= t(/*%1$s is a user name, %2$s is a date/time*/'Added by %1$s on %2$s', h($fileVersion->getAuthorName()), h($date->formatPrettyDateTime($fileVersion->getDateAdded(), true))) ?>
        </dd>
        <dt><?= t('Total Downloads') ?></dt>
        <dd><?= $number->format($file->getTotalDownloads(), 0) ?></dd>
        <dt><?= t('Most Recent Downloads') ?></dt>
        <dd>
            <?php
            if ($recentDownloads === []) {
                ?><i><?= t('No downloads') ?></i><?php
            } else {
                ?>
                <table class="table table-sm table-borderless ccm-file-manager-details-download">
                    <tbody>
                        <?php
                        foreach ($recentDownloads as $recentDownload) {
                            ?>
                        	<tr>
                                <td>
                                    <?php
                                    if ($recentDownload->getDownloaderID() === null) {
                                        ?><i><?= t('Guest') ?></i><?php
                                    } else {
                                        $downloader = User::getByUserID($recentDownload->getDownloaderID());
                                        if ($downloader && $downloader->isRegistered()) {
                                            echo h($downloader->getUserName());
                                        } else {
                                            ?><i><?= t('Deleted user (ID: %s)', $recentDownload->getDownloaderID()) ?></i><?php
                                        }
                                    }
                                    ?>
                                </td>
                                <td><?= h($date->formatPrettyDateTime($recentDownload->getDownloadDateTime(), true)) ?></td>
                                <td><?= t('Version %s', $recentDownload->getFileVersion()) ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
                <a
                    class="btn btn-secondary dialog-launch"
                    dialog-title="<?= t('Download Statistics') ?>"
                    dialog-width="620" dialog-height="400"
                    href="<?= h($resolverManager->resolve(['/ccm/system/dialogs/file/statistics', $file->getFileID()])) ?>"
                ><?= t('More') ?></a>
                <?php
            }
            ?>
            <div class="text-muted"><i><?= t('If this file is downloaded through the File Block we track it here.') ?></i></div>
        </dd>
        <dt><?= t('File Usage') ?></dt>
        <dd>
            <?php
            if ($usageRecords === []) {
                ?>
                <i><?= t("It seems that this file isn't used anywhere.") ?></i>
                <?php
            } else {
                ?>
                <table class="table table-sm table-borderless ccm-file-manager-details-usage">
                    <thead>
                        <tr>
                            <th><?= t('Page ID') ?></th>
                            <th><?= t('Version') ?></th>
                            <th><?= t('Handle') ?></th>
                            <th><?= t('Location') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($usageRecords as $usageRecord) {
                            $page = Page::getByID($usageRecord->getCollectionId(), $usageRecord->getCollectionVersionId());
                            if (!$page || $page->isError()) {
                                $page = null;
                            }
                            ?>
                            <tr>
                                <td><?= $usageRecord->getCollectionId() ?></td>
                                <td><?= $usageRecord->getCollectionVersionId() ?></strong></td>
                                <td><?= $page === null ? '<i>' . t('n/a') . '</i>' : '<strong>' . h($page->getCollectionHandle()) . '</strong>' ?></td>
                                <td>
                                    <?php
                                    if ($page === null) {
                                        ?>
                                        <i><?= t('n/a') ?></i>
                                        <?php
                                    } else {
                                        $pagePath = '/' . ltrim((string) $page->getCollectionPath(), '/');
                                        ?>
                                        <a href="<?= $resolverManager->resolve([$page]) ?>"><strong><?= h($pagePath) ?></strong></a>
                                        <?php
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
                <?php
            }
            ?>
        </dd>
    </dl>
</section>

<hr class="mt-5 mb-4" />

<section>
    <?php
    if ($filePermissions->canEditFilePermissions()) {
        ?>
        <a
            class="btn btn-secondary float-right dialog-launch"
            dialog-title="<?= t('Storage Location') ?>"
            dialog-width="500" dialog-height="400"
            href="<?= h($resolverManager->resolve(['/ccm/system/dialogs/file/bulk/storage?fID[]=' . $file->getFileID()])) ?>"
        ><?= t('Edit') ?></a>
        <?php
    }
    ?>
    <h3><?= t('Storage') ?></h3>
    <dl class="ccm-file-manager-details-storage">
        <dt><?= t('Tracked URL') ?></dt>
        <dd>
            <?= h($fileVersion->getDownloadURL()) ?>
            <div class="text-muted"><?= t("If you're going to hard code a link to this file, use this URL. By using this URL concrete5 will still be able to manage permissions and track statistics on its use.") ?></div>
        </dd>
        <dt><?= t('File in OS') ?></dt>
        <dd>
            <?= h($fileVersion->getURL()) ?>
            <div class="text-muted"><?= t('For debugging only, this is the complete URL to the source file.') ?></div>
        </dd>
        <dt><?= t('Storage Locations') ?></dt>
        <dd>
            <?= $file->getFileStorageLocationObject()->getDisplayName() ?>
            <div class="text-muted"><?= t('You can use S3 or other cloud storage solutions to distribute your content.') ?></div>
        </dd>
    </dl>
</section>

<script>
$(document).ready(function() {
    ConcreteEvent.subscribe('FileManagerReplaceFileComplete FileManagerBulkFileStorageComplete', function(e, data) {
        location.reload();
    });
});
</script>