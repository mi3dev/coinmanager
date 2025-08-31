<?php
// v1.3
use App\Helpers\Url;
use App\Helpers\Sort;
use App\Helpers\View;

function sortLink($label, $column, $currentSort, $currentDir) {
    $next  = Sort::nextDir($column, $currentSort, $currentDir);
    $url   = Url::build('periods/list', ['sort'=>$column, 'dir'=>$next, 'page'=>1]);
    $caret = ($currentSort === $column) ? ($currentDir === 'ASC' ? ' ▲' : ' ▼') : '';
    return '<a href="'.$url.'" class="hover:underline">'.$label.$caret.'</a>';
}
$routeForPager = $_GET['route'] ?? 'periods/list';
?>
<section class="space-y-4">
  <div class="flex items-center justify-between">
    <h1 class="text-lg font-semibold">Číselník – Období</h1>
    <a href="<?= Url::build('periods/create') ?>" class="rounded-md bg-sky-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-sky-400">
      + Přidat období
    </a>
  </div>

  <!-- Hledání + další filtry -->
  <div x-data="{adv:false}" class="space-y-2">
    <form method="get" class="flex gap-2">
      <input type="hidden" name="route" value="periods/list">
      <input class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm" type="text" name="q"
             placeholder="Hledat (display / name / description)…" value="<?= htmlspecialchars($q ?? '') ?>">
      <button class="rounded-md border border-slate-700 p-2 hover:bg-slate-800" title="Hledat" aria-label="Hledat">
        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M8 4a4 4 0 00-4 4 4 4 0 108 0 4 4 0 00-4-4zm-6 4a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
        </svg>
      </button>
      <button class="rounded-md border border-slate-700 p-2 hover:bg-slate-800"
              type="button" @click="adv=!adv" title="Další filtry" aria-label="Další filtry">
        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-.293.707L13 10.414V15a1 1 0 01-.553.894l-2 1A1 1 0 019 16v-5.586L3.293 6.707A1 1 0 013 6V4z"/>
        </svg>
      </button>
    </form>

    <form method="get" x-show="adv" x-transition>
      <input type="hidden" name="route" value="periods/list">
      <input type="hidden" name="q" value="<?= htmlspecialchars($q ?? '') ?>">
      <label class="mt-2 inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="show_inactive" value="1" <?= !empty($showInactive) ? 'checked' : '' ?>>
        <span>Zobrazit i neaktivní záznamy</span>
      </label>
      <div class="mt-2">
        <button class="rounded-md border border-slate-700 px-3 py-2 text-sm hover:bg-slate-800">Použít</button>
      </div>
    </form>
  </div>

  <?= View::flashHtml() ?>

  <div class="overflow-x-auto rounded-xl border border-slate-800">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-900 text-slate-400">
        <tr>
          <th class="px-3 py-2 text-left"><?= sortLink('ID','id',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-left"><?= sortLink('Display','display',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-left"><?= sortLink('Name','name',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-left">Description</th>
          <th class="px-3 py-2 text-left"><?= sortLink('Od','yearFrom',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-left"><?= sortLink('Do','yearTo',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-left"><?= sortLink('Stav','active',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-right">Akce</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-800">
        <?php foreach ($rows as $r): ?>
          <tr class="hover:bg-slate-800/40">
            <td class="px-3 py-2"><?= (int)$r['id'] ?></td>
            <td class="px-3 py-2"><?= htmlspecialchars($r['display']) ?></td>
            <td class="px-3 py-2"><?= htmlspecialchars($r['name']) ?></td>
            <td class="px-3 py-2"><?= htmlspecialchars($r['description']) ?></td>
            <td class="px-3 py-2"><?= htmlspecialchars((string)$r['yearFrom']) ?></td>
            <td class="px-3 py-2"><?= htmlspecialchars((string)$r['yearTo']) ?></td>
            <td class="px-3 py-2">
              <?php if (!empty($r['active'])): ?>
                <span class="rounded bg-emerald-600/20 px-2 py-0.5 text-emerald-300">aktivní</span>
              <?php else: ?>
                <span class="rounded bg-red-600/20 px-2 py-0.5 text-red-300">neaktivní</span>
              <?php endif; ?>
            </td>
            <td class="px-3 py-2 text-right">
              <a class="rounded-md border border-slate-700 px-2 py-1 text-xs hover:bg-slate-800"
                 href="<?= Url::build('periods/edit', ['id'=>$r['id']]) ?>">Upravit</a>

              <form class="inline-block" method="post" action="<?= Url::build('periods/toggle') ?>"
                    onsubmit="return confirm('Opravdu změnit stav?');">
                <?= View::csrfField() ?>
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <?php if (!empty($r['active'])): ?>
                  <button class="rounded-md border border-red-700 px-2 py-1 text-xs text-red-300 hover:bg-red-950/30" type="submit">Deaktivovat</button>
                <?php else: ?>
                  <button class="rounded-md border border-emerald-700 px-2 py-1 text-xs text-emerald-300 hover:bg-emerald-950/30" type="submit">Aktivovat</button>
                <?php endif; ?>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
          <tr><td colspan="8" class="px-3 py-6 text-slate-400">Žádné záznamy.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($p->pages > 1): ?>
    <div class="flex flex-wrap items-center gap-1 mt-4">
      <?php
        $urlFirst = Url::build($routeForPager, ['page'=>$p->firstPage()]);
        $urlPrev  = Url::build($routeForPager, ['page'=>$p->prevPage()]);
        $urlNext  = Url::build($routeForPager, ['page'=>$p->nextPage()]);
        $urlLast  = Url::build($routeForPager, ['page'=>$p->lastPage()]);
      ?>
      <a href="<?= $urlFirst ?>"
         class="px-3 py-1 rounded border border-slate-700 <?= $p->hasPrev() ? 'hover:bg-slate-800' : 'opacity-50 pointer-events-none' ?>">« První</a>
      <a href="<?= $urlPrev  ?>"
         class="px-3 py-1 rounded border border-slate-700 <?= $p->hasPrev() ? 'hover:bg-slate-800' : 'opacity-50 pointer-events-none' ?>">‹ Předchozí</a>

      <?php foreach ($p->window(2) as $pg): ?>
        <?php if ($pg === null): ?>
          <span class="px-2 text-slate-400">…</span>
        <?php else:
          $active = ($pg === $p->page);
          $url    = Url::build($routeForPager, ['page'=>$pg]);
        ?>
          <a href="<?= $url ?>"
             class="px-3 py-1 rounded <?= $active ? 'bg-sky-500 text-slate-950' : 'bg-slate-800 text-slate-200 hover:bg-slate-700' ?>">
            <?= $pg ?>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>

      <a href="<?= $urlNext ?>"
         class="px-3 py-1 rounded border border-slate-700 <?= $p->hasNext() ? 'hover:bg-slate-800' : 'opacity-50 pointer-events-none' ?>">Další ›</a>
      <a href="<?= $urlLast ?>"
         class="px-3 py-1 rounded border border-slate-700 <?= $p->hasNext() ? 'hover:bg-slate-800' : 'opacity-50 pointer-events-none' ?>">Poslední »</a>
    </div>
  <?php endif; ?>
</section>
