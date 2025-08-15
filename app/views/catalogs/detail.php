<?php
// v1.0
use App\Helpers\Url;
use App\Helpers\Sort;
use App\Helpers\View;

function sortLink($label,$col,$curS,$curD,$catalogId){
  $next = Sort::nextDir($col,$curS,$curD);
  $url  = Url::build('catalogs/detail',['id'=>$catalogId,'sort'=>$col,'dir'=>$next,'page'=>1]);
  $c    = ($curS===$col)?($curD==='ASC'?' ▲':' ▼'):'';
  return '<a href="'.$url.'" class="hover:underline">'.$label.$c.'</a>';
}
$routeForPager = 'catalogs/detail';
?>
<section class="space-y-6">
  <!-- Hlavička katalogu -->
  <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h1 class="text-xl font-semibold"><?= htmlspecialchars($catalog['name']) ?></h1>
        <div class="mt-1 text-sm text-slate-400">
          Rok: <span class="text-slate-200"><?= (int)$catalog['year'] ?></span>
          · Měna: <span class="text-slate-200"><?= htmlspecialchars($catalog['currency']) ?></span>
          <?php if (!empty($catalog['description'])): ?>
            <div class="mt-2"><?= nl2br(htmlspecialchars($catalog['description'])) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <div class="flex gap-2">
        <a href="<?= Url::build('catalogs/list') ?>" class="rounded-md border border-slate-700 px-3 py-2 text-sm hover:bg-slate-800">Zpět na katalogy</a>
        <a href="<?= Url::build('catalogentries/create',['catalogId'=>$catalog['id']]) ?>" class="rounded-md bg-sky-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-sky-400">+ Přidat položku</a>
      </div>
    </div>
  </div>

  <!-- Filtry -->
  <div x-data="{adv:false}" class="space-y-2">
    <form method="get" class="flex flex-wrap gap-2">
      <input type="hidden" name="route" value="catalogs/detail">
      <input type="hidden" name="id" value="<?= (int)$catalog['id'] ?>">
      <input class="min-w-[220px] grow rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
             type="text" name="q" placeholder="Hledat podle názvu nominálu/titulu…" value="<?= htmlspecialchars($q ?? '') ?>">
      <input class="w-28 rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm" type="text" name="yf" inputmode="numeric" placeholder="Rok od" value="<?= htmlspecialchars($yf ?? '') ?>">
      <input class="w-28 rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm" type="text" name="yt" inputmode="numeric" placeholder="Rok do" value="<?= htmlspecialchars($yt ?? '') ?>">
      <button class="rounded-md border border-slate-700 px-3 py-2 text-sm hover:bg-slate-800">Filtrovat</button>
      <button type="button" class="rounded-md border border-slate-700 px-3 py-2 text-sm hover:bg-slate-800" @click="adv=!adv">Další</button>
    </form>

    <form method="get" x-show="adv" x-transition>
      <input type="hidden" name="route" value="catalogs/detail">
      <input type="hidden" name="id" value="<?= (int)$catalog['id'] ?>">
      <input type="hidden" name="q"  value="<?= htmlspecialchars($q ?? '') ?>">
      <input type="hidden" name="yf" value="<?= htmlspecialchars($yf ?? '') ?>">
      <input type="hidden" name="yt" value="<?= htmlspecialchars($yt ?? '') ?>">
      <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="show_inactive" value="1" <?= !empty($showInactive)?'checked':'' ?>>
        <span>Zobrazit i neaktivní záznamy</span>
      </label>
      <div class="mt-2">
        <button class="rounded-md border border-slate-700 px-3 py-2 text-sm hover:bg-slate-800">Použít</button>
      </div>
    </form>
  </div>

  <?= View::flashHtml() ?>

  <!-- Tabulka položek -->
  <div class="overflow-x-auto rounded-xl border border-slate-800">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-900 text-slate-400">
        <tr>
          <th class="px-3 py-2 text-left"><?= sortLink('ID','id',$sort,$dir,$catalog['id']) ?></th>
          <th class="px-3 py-2 text-left"><?= sortLink('Nominál','denomination',$sort,$dir,$catalog['id']) ?></th>
          <th class="px-3 py-2 text-left"><?= sortLink('Titul','itemTitle',$sort,$dir,$catalog['id']) ?></th>
          <th class="px-3 py-2 text-left"><?= sortLink('Rok','year',$sort,$dir,$catalog['id']) ?></th>
          <th class="px-3 py-2 text-left"><?= sortLink('Vzácnost','rarity',$sort,$dir,$catalog['id']) ?></th>
          <th class="px-3 py-2 text-left">BU (tis.)</th>
          <th class="px-3 py-2 text-left">Proof (tis.)</th>
          <th class="px-3 py-2 text-left">Cena 2/2</th>
          <th class="px-3 py-2 text-left">Cena 1/1</th>
          <th class="px-3 py-2 text-left">Cena 0/0</th>
          <th class="px-3 py-2 text-left">Proof</th>
          <th class="px-3 py-2 text-left">Variant</th>
          <th class="px-3 py-2 text-left"><?= sortLink('Stav','active',$sort,$dir,$catalog['id']) ?></th>
          <th class="px-3 py-2 text-right">Akce</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-800">
        <?php foreach ($rows as $r): ?>
          <tr class="hover:bg-slate-800/40">
            <td class="px-3 py-2"><?= (int)$r['id'] ?></td>
            <td class="px-3 py-2"><?= htmlspecialchars($r['denomination'] ?? '') ?></td>
            <td class="px-3 py-2"><?= htmlspecialchars($r['itemTitle'] ?? '') ?></td>
            <td class="px-3 py-2"><?= (int)$r['year'] ?></td>
<td class="px-3 py-2" title="<?= isset($r['rarityLevel']) ? 'Level: '.$r['rarityLevel'] : '' ?>">
  <?= htmlspecialchars($r['rarityDisplay'] ?? '') ?>
</td>


            <td class="px-3 py-2"><?= htmlspecialchars($r['mintageStandard'] ?? '') ?></td>
            <td class="px-3 py-2"><?= htmlspecialchars($r['mintageProof'] ?? '') ?></td>
            <td class="px-3 py-2">
              <?= $r['price2_2']===null ? '<span class="text-slate-400">tržní</span>' : htmlspecialchars($r['price2_2']) ?>
              <span class="text-xs text-slate-400"> (<?= htmlspecialchars($r['price2_2_type']) ?>)</span>
            </td>
            <td class="px-3 py-2">
              <?= $r['price1_1']===null ? '<span class="text-slate-400">tržní</span>' : htmlspecialchars($r['price1_1']) ?>
              <span class="text-xs text-slate-400"> (<?= htmlspecialchars($r['price1_1_type']) ?>)</span>
            </td>
            <td class="px-3 py-2">
              <?= $r['price0_0']===null ? '<span class="text-slate-400">tržní</span>' : htmlspecialchars($r['price0_0']) ?>
              <span class="text-xs text-slate-400"> (<?= htmlspecialchars($r['price0_0_type']) ?>)</span>
            </td>
            <td class="px-3 py-2"><?= htmlspecialchars($r['priceProof'] ?? '') ?></td>
            <td class="px-3 py-2">
              <?php
                $vt = $r['variantType'] ?? 'none';
                echo $vt==='has_variants' ? 'má varianty' : ($vt==='is_variant' ? 'varianta' : '—');
              ?>
            </td>
            <td class="px-3 py-2">
              <?= !empty($r['active'])
                ? '<span class="rounded bg-emerald-600/20 px-2 py-0.5 text-emerald-300">aktivní</span>'
                : '<span class="rounded bg-red-600/20 px-2 py-0.5 text-red-300">neaktivní</span>'; ?>
            </td>
            <td class="px-3 py-2 text-right">
              <a class="rounded-md border border-slate-700 px-2 py-1 text-xs hover:bg-slate-800"
                 href="<?= Url::build('catalogentries/edit', ['id'=>$r['id'], 'catalogId'=>$catalog['id']]) ?>">Upravit</a>
              <form class="inline-block" method="post" action="<?= Url::build('catalogentries/toggle') ?>"
                    onsubmit="return confirm('Opravdu změnit stav?');">
                <?= View::csrfField() ?>
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="catalogId" value="<?= (int)$catalog['id'] ?>">
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
          <tr><td colspan="14" class="px-3 py-6 text-slate-400">Žádné položky v katalogu.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($p->pages > 1): ?>
    <div class="flex flex-wrap items-center gap-1 mt-4">
      <?php
        $urlFirst = Url::build($routeForPager, ['id'=>$catalog['id'],'page'=>$p->firstPage()]);
        $urlPrev  = Url::build($routeForPager, ['id'=>$catalog['id'],'page'=>$p->prevPage()]);
        $urlNext  = Url::build($routeForPager, ['id'=>$catalog['id'],'page'=>$p->nextPage()]);
        $urlLast  = Url::build($routeForPager, ['id'=>$catalog['id'],'page'=>$p->lastPage()]);
      ?>
      <a href="<?= $urlFirst ?>" class="px-3 py-1 rounded border border-slate-700 <?= $p->hasPrev()?'hover:bg-slate-800':'opacity-50 pointer-events-none' ?>">« První</a>
      <a href="<?= $urlPrev  ?>" class="px-3 py-1 rounded border border-slate-700 <?= $p->hasPrev()?'hover:bg-slate-800':'opacity-50 pointer-events-none' ?>">‹ Předchozí</a>
      <?php foreach ($p->window(2) as $pg): ?>
        <?php if ($pg===null): ?><span class="px-2 text-slate-400">…</span><?php else:
          $active = ($pg===$p->page);
          $url    = Url::build($routeForPager, ['id'=>$catalog['id'], 'page'=>$pg]); ?>
          <a href="<?= $url ?>" class="px-3 py-1 rounded <?= $active?'bg-sky-500 text-slate-950':'bg-slate-800 text-slate-200 hover:bg-slate-700' ?>"><?= $pg ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
      <a href="<?= $urlNext ?>" class="px-3 py-1 rounded border border-slate-700 <?= $p->hasNext()?'hover:bg-slate-800':'opacity-50 pointer-events-none' ?>">Další ›</a>
      <a href="<?= $urlLast ?>" class="px-3 py-1 rounded border border-slate-700 <?= $p->hasNext()?'hover:bg-slate-800':'opacity-50 pointer-events-none' ?>">Poslední »</a>
    </div>
  <?php endif; ?>
</section>
