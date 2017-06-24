<div class="col-xs-12 section-title">
  <img src="/img/people/debt/bill.svg" class="icon-section icon-money">
  <h3>
    {{ trans('people.debt_title') }}

    <span>
      <a href="/people/{{ $contact->id }}/debt/add" class="btn">{{ trans('people.debt_add_cta') }}</a>
    </span>
  </h3>
</div>

@if (!$contact->hasDebt())

  <div class="col-xs-12">
    <div class="section-blank">
      <h3>{{ trans('people.debts_blank_title', ['name' => $contact->getFirstName()]) }}</h3>
      <a href="/people/{{ $contact->id }}/debt/add">{{ trans('people.debt_add_cta') }}</a>
    </div>
  </div>

@else

  <div class="col-xs-12 debts-list">

    <ul class="table">
      @foreach($contact->getDebts() as $debt)
      <li class="table-row">
        <div class="table-cell date">
          {{ \App\Helpers\DateHelper::getShortDate($debt->created_at) }}
        </div>
        <div class="table-cell debt-nature">
          @if ($debt->in_debt == 'yes')
            {{ trans('people.debt_you_owe', [
                'amount' => MoneyHelper::format($debt->amount)
            ]) }}
          @else
            {{ trans('people.debt_they_owe', [
                'name' => $contact->getFirstName(),
                'amount' => MoneyHelper::format($debt->amount)
            ]) }}
          @endif
        </div>
        <div class="table-cell reason">
          @if (! is_null($debt->reason))
            {{ $debt->reason }}
          @endif
        </div>
        <div class="table-cell list-actions">
          <a href="{{ route('people.debt.edit', ['people' => $contact->id, 'debtId' => $debt->id]) }}">
            <i class="fa fa-pencil" aria-hidden="true"></i>
          </a>
          <a href="/people/{{ $contact->id }}/debt/{{ $debt->id }}/delete" onclick="return confirm('{{ trans('people.debt_delete_confirmation') }}')">
            <i class="fa fa-trash-o" aria-hidden="true"></i>
          </a>
        </div>
      </li>
      @endforeach
    </ul>

  </div>

@endif
