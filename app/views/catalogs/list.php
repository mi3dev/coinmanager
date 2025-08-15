<?php
// v1.2
use App\Helpers\Url;
use App\Helpers\Sort;
use App\Helpers\View;

function sortLink($label,$col,$curS,$curD){
  $next = Sort::nextDir($col,$curS,$curD);
  $url  = Url::build('catalogs/list',['sort'=>$col,'dir'=>$next,'page'=>1]);
  $c = ($curS===$col)?($curD==='ASC'?' ▲':' ▼'):'';
  return '<a href="'.$url.'" class="hover:underline">'.$label.$c.'</a>';
}
$routeForPager = $_GET['route'] ?? 'catalogs/list';
?>
<section class="space-y-4">
  <div class="flex items-center justify-between">
    <h1 class="text-lg font-semibold">Katalogy</h1>
    <a href="<?= Url::build('catalogs/create') ?>" class="rounded-md bg-sky-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-sky-400">+ Nový katalog</a>
  </div>

  <div x-data="{adv:false}" class="space-y-2">
    <form method="get" class="flex gap-2">
      <input type="hidden" name="route" value="catalogs/list">
      <input class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
             type="text" name="q" placeholder="Hledat v názvu, měně, popisu…"
             value="<?= htmlspecialchars($q ?? '') ?>">
      <button class="rounded-md border border-slate-700 px-3 py-2 text-sm hover:bg-slate-800">Hledat</button>
      <button class="rounded-md border border-slate-700 px-3 py-2 text-sm hover:bg-slate-800" type="button" @click="adv=!adv">Další filtry</button>
    </form>

    <form method="get" x-show="adv" x-transition>
      <input type="hidden" name="route" value="catalogs/list">
      <input type="hidden" name="q" value="<?= htmlspecialchars($q ?? '') ?>">
      <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="show_inactive" value="1" <?= !empty($showInactive)?'checked':'' ?>>
        <span>Zobrazit i neaktivní</span>
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
          <th class="px-3 py-2 text-left"><?= sortLink('Název','name',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-left"><?= sortLink('Rok','year',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-left"><?= sortLink('Měna','currency',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-left"><?= sortLink('Stav','active',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-left"><?= sortLink('Vytvořen','created_at',$sort,$dir) ?></th>
          <th class="px-3 py-2 text-right">Akce</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-800">
        <?php foreach ($rows as $r): ?>
          <tr class="hover:bg-slate-800/40">
            <td class="px-3 py-2"><?= (int)$r['id'] ?></td>
            <td class="px-3 py-2">
              <a href="<?= Url::build('catalogs/detail',['id'=>$r['id']]) ?>" class="text-sky-400 hover:underline">
                <?= htmlspecialchars($r['name']) ?>
              </a>
            </td>
            <td class="px-3 py-2"><?= (int)$r['year'] ?></td>
            <td class="px-3 py-2"><?= htmlspecialchars($r['currency']) ?></td>
            <td class="px-3 py-2">
              <?= !empty($r['active'])
                ? '<span class="rounded bg-emerald-600/20 px-2 py-0.5 text-emerald-300">aktivní</span>'
                : '<span class="rounded bg-red-600/20 px-2 py-0.5 text-red-300">neaktivní</span>'; ?>
            </td>
            <td class="px-3 py-2"><?= htmlspecialchars($r['created_at'] ?? '') ?></td>
            <td class="px-3 py-2 text-right">
              <a class="rounded-md border border-slate-700 px-2 py-1 text-xs hover:bg-slate-800"
                 href="<?= Url::build('catalogs/edit',['id'=>$r['id']]) ?>">Upravit</a>
              <a class="rounded-md border border-slate-700 px-2 py-1 text-xs hover:bg-slate-800"
                 href="<?= Url::build('catalogs/detail',['id'=>$r['id']]) ?>">Detail</a>
              <form class="inline-block" method="post" action="<?= Url::build('catalogs/toggle') ?>"
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
          <tr><td colspan="7" class="px-3 py-6 text-slate-400">Žádné záznamy.</td></tr>
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
      <a href="<?= $urlFirst ?>" class="px-3 py-1 rounded border border-slate-700 <?= $p->hasPrev()?'hover:bg-slate-800':'opacity-50 pointer-events-none' ?>">« První</a>
      <a href="<?= $urlPrev  ?>" class="px-3 py-1 rounded border border-slate-700 <?= $p->hasPrev()?'hover:bg-slate-800':'opacity-50 pointer-events-none' ?>">‹ Předchozí</a>
      <?php foreach ($p->window(2) as $pg): ?>
        <?php if ($pg===null): ?>
          <span class="px-2 text-slate-400">…</span>
        <?php else:
          $active = ($pg===$p->page);
          $url=Url::build($routeForPager,['page'=>$pg]); ?>
          <a href="<?= $url ?>" class="px-3 py-1 rounded <?= $active?'bg-sky-500 text-slate-950':'bg-slate-800 text-slate-200 hover:bg-slate-700' ?>"><?= $pg ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
      <a href="<?= $urlNext ?>" class="px-3 py-1 rounded border border-slate-700 <?= $p->hasNext()?'hover:bg-slate-800':'opacity-50 pointer-events-none' ?>">Další ›</a>
      <a href="<?= $urlLast ?>" class="px-3 py-1 rounded border border-slate-700 <?= $p->hasNext()?'hover:bg-slate-800':'opacity-50 pointer-events-none' ?>">Poslední »</a>
    </div>
  <?php endif; ?>
</section>
