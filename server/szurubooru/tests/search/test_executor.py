import unittest.mock
from szurubooru import search
from szurubooru.func import cache

def test_retrieving_from_cache(user_factory):
    config = unittest.mock.MagicMock()
    with unittest.mock.patch('szurubooru.func.cache.has'), \
            unittest.mock.patch('szurubooru.func.cache.get'):
        cache.has.side_effect = lambda *args: True
        executor = search.Executor(config)
        executor.execute('test:whatever', 1, 10)
        assert cache.get.called

def test_putting_equivalent_queries_into_cache(user_factory):
    config = search.configs.PostSearchConfig()
    with unittest.mock.patch('szurubooru.func.cache.has'), \
            unittest.mock.patch('szurubooru.func.cache.put'):
        hashes = []
        def appender(key, value):
            hashes.append(key)
        cache.has.side_effect = lambda *args: False
        cache.put.side_effect = appender
        executor = search.Executor(config)
        executor.execute('safety:safe test', 1, 10)
        executor.execute('safety:safe  test', 1, 10)
        executor.execute('safety:safe test ', 1, 10)
        executor.execute(' safety:safe test', 1, 10)
        executor.execute(' SAFETY:safe test', 1, 10)
        executor.execute('test safety:safe', 1, 10)
        assert len(hashes) == 6
        assert len(set(hashes)) == 1

def test_putting_non_equivalent_queries_into_cache(user_factory):
    config = search.configs.PostSearchConfig()
    with unittest.mock.patch('szurubooru.func.cache.has'), \
            unittest.mock.patch('szurubooru.func.cache.put'):
        hashes = []
        def appender(key, value):
            hashes.append(key)
        cache.has.side_effect = lambda *args: False
        cache.put.side_effect = appender
        executor = search.Executor(config)
        args = [
            ('', 1, 10),
            ('creation-time:2016', 1, 10),
            ('creation-time:2015', 1, 10),
            ('creation-time:2016-01', 1, 10),
            ('creation-time:2016-02', 1, 10),
            ('creation-time:2016-01-01', 1, 10),
            ('creation-time:2016-01-02', 1, 10),
            ('tag-count:1,3', 1, 10),
            ('tag-count:1,2', 1, 10),
            ('tag-count:1', 1, 10),
            ('tag-count:1..3', 1, 10),
            ('tag-count:1..4', 1, 10),
            ('tag-count:2..3', 1, 10),
            ('tag-count:1..', 1, 10),
            ('tag-count:2..', 1, 10),
            ('tag-count:..3', 1, 10),
            ('tag-count:..4', 1, 10),
            ('-tag-count:1..3', 1, 10),
            ('-tag-count:1..4', 1, 10),
            ('-tag-count:2..3', 1, 10),
            ('-tag-count:1..', 1, 10),
            ('-tag-count:2..', 1, 10),
            ('-tag-count:..3', 1, 10),
            ('-tag-count:..4', 1, 10),
            ('safety:safe', 1, 10),
            ('safety:safe', 1, 20),
            ('safety:safe', 2, 10),
            ('safety:sketchy', 1, 10),
            ('safety:safe test', 1, 10),
            ('-safety:safe', 1, 10),
            ('-safety:safe', 1, 20),
            ('-safety:safe', 2, 10),
            ('-safety:sketchy', 1, 10),
            ('-safety:safe test', 1, 10),
            ('safety:safe -test', 1, 10),
            ('-test', 1, 10),
        ]
        for arg in args:
            executor.execute(*arg)
        assert len(hashes) == len(args)
        assert len(set(hashes)) == len(args)
