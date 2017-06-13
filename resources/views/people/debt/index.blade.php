<div class="col-xs-12 section-title">
  <img src="{{ asset('/img/people/debt/bill.svg') }}" class="icon-section icon-money">
  <h3>
    {{ trans('people.debt_title') }}

    <span>
      <a href="{{ route('people.debt.add', ['people' => $contact->id]) }}" class="btn">{{ trans('people.debt_add_cta') }}</a>
    </span>
  </h3>
</div>

@if (!$contact->hasDebt())

  <div class="col-xs-12">
    <div class="section-blank">
      <h3>{{ trans('people.debts_blank_title', ['name' => $contact->getFirstName()]) }}</h3>
      <a href="{{ route('people.debt.add', ['people' => $contact->id]) }}">{{ trans('people.debt_add_cta') }}</a>
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
            {{ trans('people.debt_you_owe', ['amount' => $debt->amount]) }}
          @else
            {{ trans('people.debt_they_owe', ['name' => $contact->getFirstName(), 'amount' => $debt->amount]) }}
          @endif
        </div>
        <div class="table-cell reason">
          @if (! is_null($debt->reason))
            {{ $debt->reason }}
          @endif
        </div>
        <div class="table-cell list-actions">
          <a href="{{ route('people.debt.delete', ['people' => $contact->id, 'debtId' => $debt->id]) }}" onclick="return confirm('{{ trans('people.debt_delete_confirmation') }}')">
            <i class="fa fa-trash-o" aria-hidden="true"></i>
          </a>
        </div>
      </li>
      @endforeach
    </ul>

  </div>

@endif
