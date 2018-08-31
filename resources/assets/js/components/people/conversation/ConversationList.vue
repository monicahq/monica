<style scoped>
</style>

<template>
    <div>
        <vue-good-table
            :columns="columns"
            :rows="conversations"
            styleClass="vgt-table"
            :sort-options="{
                enabled: false
            }"
            @on-row-click="onRowClick"
            :pagination-options="{
                enabled: true
            }">
            <template slot="table-row" slot-scope="props">
                <span v-if="props.column.field == 'message_count'">
                    <span>
                        {{props.row.message_count}}
                        <svg width="18" height="15" viewBox="0 0 18 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M2.29412 14.6014V12.8182H1.69853C0.772058 12.8182 0 12.0839 0 11.2028V6.67133C0 5.79021 0.772058 5.05594 1.69853 5.05594H5.60294C5.82353 5.05594 6.02206 5.22378 6.02206 5.45455C6.02206 5.68531 5.84559 5.85315 5.60294 5.85315H1.69853C1.21323 5.85315 0.816176 6.23077 0.816176 6.69231V11.2238C0.816176 11.6853 1.21323 12.0629 1.69853 12.0629H2.71324C2.93382 12.0629 3.13235 12.2308 3.13235 12.4615V13.6993L4.80882 12.1678C4.875 12.1049 4.9853 12.0629 5.09559 12.0629H9.52941C10.0147 12.0629 10.4118 11.6853 10.4118 11.2238V8.91608C10.4118 8.70629 10.5882 8.51748 10.8309 8.51748C11.0515 8.51748 11.25 8.68532 11.25 8.91608V11.2238C11.25 12.1049 10.4779 12.8392 9.55147 12.8392H5.25L3 14.8951C2.91177 14.958 2.82353 15 2.71324 15C2.66912 15 2.60294 15 2.55882 14.958C2.40441 14.8951 2.29412 14.7482 2.29412 14.6014ZM12.7059 7.84615H8.40441C7.45588 7.84615 6.68382 7.11188 6.68382 6.20979V1.63636C6.68382 0.734263 7.45588 0 8.40441 0H16.2794C17.2279 0 18 0.734263 18 1.63636V6.20979C18 7.11188 17.2279 7.84615 16.2794 7.84615H15.6838V9.62937C15.6838 9.7972 15.5956 9.92307 15.4412 9.98601C15.3971 10.007 15.3309 10.028 15.2868 10.028C15.1765 10.028 15.0882 9.98601 15 9.92307L12.7059 7.84615ZM8.40441 7.06993H12.8603C12.9706 7.06993 13.0588 7.11188 13.1471 7.17482L14.8456 8.72727V7.46853C14.8456 7.25874 15.0221 7.06993 15.2647 7.06993H16.2794C16.7647 7.06993 17.1618 6.6923 17.1618 6.23077V1.65734C17.1618 1.1958 16.7647 0.818181 16.2794 0.818181H8.40441C7.91912 0.818181 7.52206 1.1958 7.52206 1.65734V6.23077C7.52206 6.6923 7.91912 7.06993 8.40441 7.06993ZM12.3529 3.54545C12.0441 3.54545 11.7794 3.7972 11.7794 4.09091C11.7794 4.38462 12.0441 4.63636 12.3529 4.63636C12.6618 4.63636 12.9265 4.38462 12.9265 4.09091C12.9265 3.7972 12.6618 3.54545 12.3529 3.54545ZM14.4926 4.63636C14.8015 4.63636 15.0662 4.38462 15.0662 4.09091C15.0662 3.7972 14.8015 3.54545 14.4926 3.54545C14.1838 3.54545 13.9191 3.7972 13.9191 4.09091C13.9191 4.38462 14.1838 4.63636 14.4926 4.63636ZM10.1912 3.54545C9.88235 3.54545 9.61765 3.7972 9.61765 4.09091C9.61765 4.38462 9.88235 4.63636 10.1912 4.63636C10.5 4.63636 10.7647 4.38462 10.7647 4.09091C10.7647 3.7972 10.5 3.54545 10.1912 3.54545Z" fill="#67718A"/>
                        </svg>
                    </span>
                </span>
                <span v-else>
                    {{props.formattedRow[props.column.field]}}
                </span>
            </template>
        </vue-good-table>

    </div>
</template>

<script>
    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                conversations: [],

                columns: [
                    {
                        label: this.$t('app.date'),
                        field: 'happened_at',
                        tdClass: 'vgt-table-date',
                    },
                    {
                        label: this.$t('app.type'),
                        field: 'contact_field_type',
                        width: '110px',
                    },
                    {
                        label: this.$t('people.conversation_list_table_messages'),
                        field: 'message_count',
                        width: '110px',
                    },
                    {
                        label: this.$t('people.conversation_list_table_content'),
                        field: 'content',
                    }
                ],
            };
        },

        props: {
            hash: {
                type: String,
            },
        },

        mounted() {
            this.prepareComponent(this.hash)
        },

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent(hash) {
                axios.get('/people/' + hash + '/conversations')
                    .then(response => {
                        this.conversations = response.data;
                    });
            },

            onRowClick(params) {
                window.location.href = params.row.route;
            }
        }
    }
</script>
